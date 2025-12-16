<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\Group;
use App\Services\AttachmentService;
use App\Services\AuditService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private AttachmentService $attachmentService;
    private NotificationService $notificationService;
    private AuditService $auditService;

    public function __construct(
        PaymentService $paymentService,
        AttachmentService $attachmentService,
        NotificationService $notificationService,
        AuditService $auditService
    ) {
        $this->paymentService = $paymentService;
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
        $this->auditService = $auditService;
    }

    /**
     * Display payment history for a group.
     */
    public function groupPaymentHistory(Group $group)
    {
        // Check if user is member of group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        $user = auth()->user();

        // Get payments for this group - only show payments where current user is the split user (person who owes)
        $payments = Payment::whereHas('split.expense', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })
        ->whereHas('split', function ($q) use ($user) {
            // Show only payments for splits belonging to current user
            $q->where('user_id', $user->id)
            // Exclude payments where the split user is the same as the expense payer (self-payment)
            ->whereRaw('`expense_splits`.`user_id` != (SELECT `payer_id` FROM `expenses` WHERE `expenses`.`id` = `expense_splits`.`expense_id`)');
        })
        ->with([
            'split.user',
            'split.expense.payer',
            'split.expense.group',
            'paidBy',
            'attachments'
        ])
        ->latest()
        ->paginate(20);

        // Get settlement for this user in this group
        // Load all necessary relationships including payments for accurate settlement calculation
        $group->load([
            'expenses' => function ($query) {
                $query->latest();
            },
            'expenses.splits.user',
            'expenses.splits.contact',
            'expenses.splits.payment',
            'expenses.payer',
            'members',
            'contacts'
        ]);

        // Check if user is admin
        $isAdmin = $group->isAdmin($user);

        // Always calculate user's personal settlement
        $personalSettlement = $this->calculateSettlement($group, $user);

        // Calculate overall settlement matrix for all group members (visible to everyone)
        $overallSettlement = $this->calculateGroupSettlementMatrix($group);

        // Get complete transaction history for the group
        $transactionHistory = $this->getGroupTransactionHistory($group);

        // Calculate settlement suggestions (optimized payment instructions)
        $settlementSuggestions = $this->calculateSettlementSuggestions($group);

        // Calculate category breakdown
        $categoryBreakdown = $this->calculateCategoryBreakdown($group);

        return view('groups.payments.history', compact('group', 'payments', 'personalSettlement', 'overallSettlement', 'isAdmin', 'transactionHistory', 'settlementSuggestions', 'categoryBreakdown'));
    }

    /**
     * Generate standardized breakdown text for settlement between two people.
     * Format: Shows expenses from both parties, then adjustments, then total.
     */
    private function generateSettlementBreakdown($group, $fromUser, $toUser, $item)
    {
        $breakdown = "Settlement: {$fromUser->name} ↔ {$toUser->name}\n\n";

        // Separate expenses by type
        $fromUserExpenses = [];  // Expenses paid by fromUser
        $toUserExpenses = [];    // Expenses paid by toUser
        $advances = 0;
        $payments = 0;

        if (isset($item['expenses']) && count($item['expenses']) > 0) {
            foreach ($item['expenses'] as $exp) {
                if ($exp['type'] === 'you_owe') {
                    // toUser paid, fromUser owes
                    $toUserExpenses[] = $exp;
                } elseif ($exp['type'] === 'they_owe') {
                    // fromUser paid, toUser owes
                    $fromUserExpenses[] = $exp;
                } elseif ($exp['type'] === 'advance') {
                    // Only count advances if fromUser paid them (they reduce fromUser's balance)
                    // Don't add advances that are unrelated to this pair
                    $advances += $exp['amount'];
                } elseif ($exp['type'] === 'payment_received') {
                    $payments += $exp['amount'];
                } elseif ($exp['type'] === 'payment_sent') {
                    $payments += $exp['amount'];
                }
            }
        }

        // fromUser's expenses section (expenses paid by fromUser that toUser owes)
        if (count($fromUserExpenses) > 0) {
            $fromTotal = 0;
            foreach ($fromUserExpenses as $exp) {
                $fromTotal += $exp['amount'];
            }
            $breakdown .= "{$fromUser->name}'s expenses: $" . number_format($fromTotal, 2) . "\n";
        }

        // toUser's expenses section (expenses paid by toUser that fromUser owes)
        if (count($toUserExpenses) > 0) {
            $toTotal = 0;
            foreach ($toUserExpenses as $exp) {
                $toTotal += $exp['amount'];
            }
            $breakdown .= "{$toUser->name}'s expenses: -$" . number_format($toTotal, 2) . "\n";
        }
        
        // Add blank line if there were expenses
        if (count($fromUserExpenses) > 0 || count($toUserExpenses) > 0) {
            $breakdown .= "\n";
        }

        // Adjustments section - only show if there are actual adjustments
        $hasAdjustments = $payments > 0 || $advances > 0;
        if ($hasAdjustments) {
            $breakdown .= "Adjustments:\n";
            if ($payments > 0) {
                $breakdown .= "- Payment received: -$" . number_format($payments, 2) . "\n";
            }
            if ($advances > 0) {
                $breakdown .= "- Advances: -$" . number_format($advances, 2) . "\n";
            }
            $breakdown .= "\n";
        }

        // Final section
        $breakdown .= "Final Balance: $" . number_format(round($item['net_amount'], 2), 2) . "\n";
        if ($item['net_amount'] > 0) {
            $breakdown .= "({$fromUser->name} owes {$toUser->name})";
        } elseif ($item['net_amount'] < 0) {
            $breakdown .= "({$toUser->name} owes {$fromUser->name})";
        } else {
            $breakdown .= "(Settled)";
        }

        return $breakdown;
    }

    /**
     * Calculate settlement for a user in a group.
     * Returns net balance with each person (positive = user owes, negative = person owes user).
     */
    public function calculateSettlement(Group $group, $user)
    {
        // Maps to track amounts owed between user and each other person
        $netBalances = [];  // User ID => [user_obj, net_amount, status, expenses]

        // Ensure all expenses are loaded (including those from earlier queries)
        // Fetch fresh to guarantee all data is included
        $expenses = \App\Models\Expense::where('group_id', $group->id)
            ->with([
                'splits' => function ($q) {
                    $q->with(['payment', 'user', 'contact']);
                },
                'payer'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            // Skip itemwise expenses - they don't create splits and don't affect settlement
            if ($expense->split_type === 'itemwise') {
                continue;
            }

            // Skip expenses where user is both payer and sole participant (self-payment)
            $firstSplit = $expense->splits->first();
            if ($expense->payer_id === $user->id && $expense->splits->count() === 1 && $firstSplit && $firstSplit->user_id === $user->id) {
                continue;
            }

            // Handle regular splits (equal, custom) - process user splits and contact splits where user is payer
            foreach ($expense->splits as $split) {
                // For contact splits, only process if user is the payer
                if ($split->contact_id && !$split->user_id) {
                    // This is a pure contact split - skip unless user is payer
                    if ($expense->payer_id !== $user->id) {
                        continue;
                    }
                }

                if ($split->user_id === $user->id && $split->user_id !== $expense->payer_id) {
                    // User is a participant and is not the payer
                    $payment = $split->payment;

                    if (!$payment || $payment->status !== 'paid') {
                        // Only process splits with non-zero amounts
                        if ($split->share_amount > 0) {
                            $payerId = $expense->payer_id;
                            if (!isset($netBalances[$payerId])) {
                                $netBalances[$payerId] = [
                                    'user' => $expense->payer,
                                    'net_amount' => 0,
                                    'status' => 'pending',
                                    'expenses' => [],
                                    'split_ids' => [],
                                ];
                            }
                            $netBalances[$payerId]['net_amount'] += $split->share_amount;
                            $netBalances[$payerId]['split_ids'][] = $split->id;
                            $netBalances[$payerId]['expenses'][] = [
                                'title' => $expense->title,
                                'amount' => $split->share_amount,
                                'type' => 'you_owe',  // User owes the payer
                            ];
                        }
                    }
                } elseif ($expense->payer_id === $user->id && $split->user_id && $split->user_id !== $user->id) {
                    // User is the payer, someone else (a user, not contact) is a participant
                    $payment = $split->payment;

                    if (!$payment || $payment->status !== 'paid') {
                        $memberId = $split->user_id;
                        if (!isset($netBalances[$memberId])) {
                            $netBalances[$memberId] = [
                                'user' => $split->user,
                                'net_amount' => 0,
                                'status' => 'pending',
                                'expenses' => [],
                            ];
                        }
                        $netBalances[$memberId]['net_amount'] -= $split->share_amount;
                        $netBalances[$memberId]['expenses'][] = [
                            'title' => $expense->title,
                            'amount' => $split->share_amount,
                            'type' => 'they_owe',  // Member owes the user (who paid)
                        ];
                    }
                } elseif ($expense->payer_id === $user->id && $split->contact_id && !$split->user_id) {
                    // User is the payer, a contact is a participant (contacts owe user money)
                    $contactId = $split->contact_id;
                    if (!isset($netBalances[$contactId])) {
                        $netBalances[$contactId] = [
                            'user' => $split->contact,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                            'is_contact' => true,
                        ];
                    }
                    $netBalances[$contactId]['net_amount'] -= $split->share_amount;
                    $netBalances[$contactId]['expenses'][] = [
                        'title' => $expense->title,
                        'amount' => $split->share_amount,
                        'type' => 'they_owe',  // Contact owes the user (who paid)
                    ];
                }
            }
        }

        // Account for advances
        // Advances are sent by specific users TO specific users
        // They should only be applied to the settlement between senders and the recipient
        // Track: [senderId][recipientId] => total_credit
        $advanceCreditPerPersonPair = []; // senderId => [recipientId => amount]
        $advanceTypePerPersonPair = []; // senderId => [recipientId => type] ('advance' or 'manual_settlement')

        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        foreach ($advances as $advance) {
            $recipientId = $advance->sent_to_user_id;
            $senders = $advance->senders;

            // Determine if this is a manual settlement or regular advance
            $isManualSettlement = strpos($advance->description, 'Manual settlement') !== false;

            // Get recipient's family count
            $recipientFamilyCount = $group->members()
                ->where('user_id', $recipientId)
                ->first()
                ?->pivot
                ?->family_count ?? 1;
            if ($recipientFamilyCount <= 0) $recipientFamilyCount = 1;

            // Only apply advance to senders - they get a credit for paying on behalf of recipient
            foreach ($senders as $sender) {
                $senderId = $sender->id;

                // Get sender's family count
                $senderFamilyCount = $group->members()
                    ->where('user_id', $senderId)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;

                if ($senderFamilyCount <= 0) {
                    $senderFamilyCount = 1;
                }

                // Advance amount divided by sender's family count = per-person credit
                $perPersonCredit = $advance->amount_per_person / $senderFamilyCount;

                // Each sender gets credit for the advance they paid
                // Credit = per-person-credit × sender's family count
                $senderAdvanceCredit = $perPersonCredit * $senderFamilyCount;

                // Apply advance credit to the correct settlement:
                // CASE 1: If current user IS the sender, they get credit in their settlement with recipient
                // CASE 2: If current user IS the recipient, they owe less to the sender

                if ($senderId === $user->id && isset($netBalances[$recipientId])) {
                    // CASE 1: User is the SENDER of the advance to recipient
                    // The advance means user paid on behalf of recipient, so recipient owes MORE
                    // SUBTRACT to make balance more negative (recipient owes sender more)
                    // Example: +50.12 (sender owes recipient) - 200 (advance) = -149.88 (recipient owes sender)
                    $netBalances[$recipientId]['net_amount'] -= $senderAdvanceCredit;

                    // Track advance per sender-recipient pair
                    if (!isset($advanceCreditPerPersonPair[$senderId])) {
                        $advanceCreditPerPersonPair[$senderId] = [];
                        $advanceTypePerPersonPair[$senderId] = [];
                    }
                    if (!isset($advanceCreditPerPersonPair[$senderId][$recipientId])) {
                        $advanceCreditPerPersonPair[$senderId][$recipientId] = 0;
                        $advanceTypePerPersonPair[$senderId][$recipientId] = $isManualSettlement ? 'manual_settlement' : 'advance';
                    }
                    $advanceCreditPerPersonPair[$senderId][$recipientId] += $senderAdvanceCredit;

                } elseif ($recipientId === $user->id && isset($netBalances[$senderId])) {
                    // CASE 2: User is the RECIPIENT of the advance from sender
                    // The advance INCREASES what user owes to sender (recipient owes more)
                    // Semantics: positive net_amount = user owes sender, negative = sender owes user
                    // Receiving advance means user owes MORE, so ADD to increase debt
                    // Example: +50.12 (user owes sender) + 200 (advance received) = +250.12 (user owes sender more)
                    $netBalances[$senderId]['net_amount'] += $senderAdvanceCredit;

                    // Track this as an advance received
                    if (!isset($advanceCreditPerPersonPair[$senderId])) {
                        $advanceCreditPerPersonPair[$senderId] = [];
                        $advanceTypePerPersonPair[$senderId] = [];
                    }
                    if (!isset($advanceCreditPerPersonPair[$senderId][$recipientId])) {
                        $advanceCreditPerPersonPair[$senderId][$recipientId] = 0;
                        $advanceTypePerPersonPair[$senderId][$recipientId] = $isManualSettlement ? 'manual_settlement' : 'advance';
                    }
                    $advanceCreditPerPersonPair[$senderId][$recipientId] += $senderAdvanceCredit;
                }
            }
        }

        // Add aggregated advance entries to relevant settlements
        // Advances appear in two cases:
        // 1. Sender's settlement: "Advance paid" or "Payment Sent" (credit, reduces their debt)
        // 2. Recipient's settlement: "Advance received" or "Payment Received" (credit, reduces their debt)
        foreach ($advanceCreditPerPersonPair as $senderId => $recipients) {
            foreach ($recipients as $recipientId => $advanceAmount) {
                $isManualSettlement = isset($advanceTypePerPersonPair[$senderId][$recipientId]) &&
                                     $advanceTypePerPersonPair[$senderId][$recipientId] === 'manual_settlement';

                if ($senderId === $user->id && isset($netBalances[$recipientId])) {
                    // CASE 1: Current user is the SENDER
                    $title = $isManualSettlement ? 'Payment sent' : 'Advance paid';
                    $netBalances[$recipientId]['expenses'][] = [
                        'title' => $title,
                        'amount' => $advanceAmount,
                        'type' => 'advance',
                    ];
                } elseif ($recipientId === $user->id && isset($netBalances[$senderId])) {
                    // CASE 2: Current user is the RECIPIENT
                    $title = $isManualSettlement ? 'Payment received' : 'Advance received';
                    $netBalances[$senderId]['expenses'][] = [
                        'title' => $title,
                        'amount' => $advanceAmount,
                        'type' => 'advance',
                    ];
                }
            }
        }

        // Account for received payments
        // These reduce what the user owes to others (payment received FROM them)
        // Received payments are actual cash amounts and should NOT be adjusted by family count
        $receivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('to_user_id', $user->id)
            ->with(['fromUser'])
            ->get();

        foreach ($receivedPayments as $receivedPayment) {
            $fromUserId = $receivedPayment->from_user_id;

            // If this person is in the settlement, reduce what they owe
            if (isset($netBalances[$fromUserId])) {
                // Use the received payment amount as-is (it's actual cash received)
                $amount = $receivedPayment->amount;

                // Payment received FROM someone settles part of the debt
                // Semantics: positive net_amount = user owes them, negative = they owe user
                // Payment received means they paid you, so ADD to settle
                // Example: -284.52 (they owe user) + 334.64 (payment) = +50.12
                $netBalances[$fromUserId]['net_amount'] += $amount;
                
                // Add to expenses array so it shows in breakdown
                $netBalances[$fromUserId]['expenses'][] = [
                    'title' => 'Payment received',
                    'amount' => $amount,
                    'type' => 'payment_received',
                ];
            }
        }

        // Account for payments sent by user to others
        // These settle what user owes to others
        $sentPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('from_user_id', $user->id)
            ->with(['toUser'])
            ->get();

        foreach ($sentPayments as $sentPayment) {
            $toUserId = $sentPayment->to_user_id;

            // If this person is in the settlement, this payment settles part of the debt
            if (isset($netBalances[$toUserId])) {
                // Use the sent payment amount as-is (it's actual cash sent)
                $amount = $sentPayment->amount;

                // Payment sent TO someone settles the debt
                // SUBTRACT to reduce what user owes them
                // Example: +284.52 (user owes them) - 334.64 (payment sent) = -50.12
                $netBalances[$toUserId]['net_amount'] -= $amount;
                
                // Add to expenses array so it shows in breakdown
                $netBalances[$toUserId]['expenses'][] = [
                    'title' => 'Payment sent',
                    'amount' => $amount,
                    'type' => 'payment_sent',
                ];
            }
        }

        // Convert to settlement array, filtering out zero balances
        // Positive net_amount = user owes this person
        // Negative net_amount = this person owes user (we show as positive amount they owe)
        $settlements = [];
        foreach ($netBalances as $personId => $data) {
            if ($data['net_amount'] != 0) {
                $settlements[] = [
                    'user' => $data['user'],
                    'amount' => abs($data['net_amount']),  // Final amount after all calculations including advances
                    'net_amount' => $data['net_amount'],  // Positive = user owes, Negative = user is owed
                    'status' => $data['status'],
                    'expenses' => $data['expenses'] ?? [],  // List of expenses contributing to this settlement
                    'split_ids' => $data['split_ids'] ?? [],  // Split IDs for all expenses in this settlement (used for marking as paid)
                    'is_contact' => $data['is_contact'] ?? false,  // Flag to indicate if this is a contact
                ];
            }
        }

        return $settlements;
    }

    /**
     * Get complete transaction history for a group (all expenses and payments).
     * Returns chronologically sorted array of all transactions.
     */
    private function getGroupTransactionHistory(Group $group)
    {
        $transactions = [];

        // Get all expenses in the group
        $expenses = Expense::where('group_id', $group->id)
            ->with(['payer', 'splits.user', 'splits.payment', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            $transactions[] = [
                'type' => 'expense',
                'timestamp' => $expense->created_at,
                'payer' => $expense->payer,
                'payer_id' => $expense->payer_id,
                'title' => $expense->title,
                'amount' => $expense->amount,
                'description' => "Paid $" . $expense->amount . " for " . $expense->title,
                'participants_count' => $expense->splits->count(),
                'split_type' => $expense->split_type,
                'expense_id' => $expense->id,
                'attachments' => $expense->attachments,
                'has_attachments' => $expense->attachments->count() > 0,
            ];
        }

        // Get all payments marked as paid in the group
        $payments = Payment::whereHas('split.expense', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })
        ->where('status', 'paid')
        ->with(['split.user', 'split.expense.payer', 'paidBy'])
        ->orderBy('created_at', 'desc')
        ->get();

        foreach ($payments as $payment) {
            $transactions[] = [
                'type' => 'payment',
                'timestamp' => $payment->created_at,
                'payer' => $payment->split->user,
                'payer_id' => $payment->split->user_id,
                'recipient' => $payment->split->expense->payer,
                'recipient_id' => $payment->split->expense->payer_id,
                'title' => $payment->split->expense->title,
                'amount' => $payment->split->share_amount,
                'description' => $payment->split->user->name . " paid " . $payment->split->expense->payer->name . " $" . number_format($payment->split->share_amount, 2),
                'paid_by' => $payment->paidBy,
                'paid_date' => $payment->paid_date,
                'payment_id' => $payment->id,
            ];
        }

        // Sort all transactions by timestamp (newest first)
        usort($transactions, function ($a, $b) {
            return $b['timestamp']->timestamp <=> $a['timestamp']->timestamp;
        });

        return $transactions;
    }

    /**
     * Calculate settlement matrix for all members in a group.
     * Returns array with structure: [$fromUserId][$toUserId] = amount owed
     */
    private function calculateGroupSettlementMatrix(Group $group)
    {
        // Initialize result array
        $result = [];
        
        // Get all group members (users only, not contacts)
        $groupMembers = $group->groupMembers()->with('user')->whereNotNull('user_id')->get();
        
        // Initialize result for all members
        foreach ($groupMembers as $groupMember) {
            if (!$groupMember->user) continue;
            
            $result[$groupMember->id] = [
                'user' => $groupMember->user,
                'is_contact' => false,
                'owes' => []
            ];
        }
        
        // Store processed pairs to avoid duplicates and ensure consistency
        $processedPairs = [];
        
        // For each member, calculate their settlement with all other members
        foreach ($groupMembers as $member) {
            if (!$member->user) continue;
            
            // Get this member's settlement with everyone
            $settlement = $this->calculateSettlement($group, $member->user);
            
            // Process each settlement item
            foreach ($settlement as $item) {
                $amount = $item['net_amount'];
                
                $targetIsContact = isset($item['is_contact']) && $item['is_contact'];
                
                if ($targetIsContact) {
                    // Skip contacts for now
                    continue;
                }
                
                // Find the target user's group member ID
                $targetUserId = $item['user']->id;
                $targetGroupMember = $groupMembers->firstWhere('user_id', $targetUserId);
                
                if (!$targetGroupMember) continue;
                
                // Create a unique pair key (always use lower ID first to ensure consistency)
                $pairKey = $member->id < $targetGroupMember->id 
                    ? "{$member->id}-{$targetGroupMember->id}" 
                    : "{$targetGroupMember->id}-{$member->id}";
                
                // Skip if we've already processed this pair
                if (isset($processedPairs[$pairKey])) continue;
                
                // Mark this pair as processed
                $processedPairs[$pairKey] = true;
                
                // Store settled pairs (amount = 0) with their expense history
                if (abs($amount) < 0.01) {
                    // Fully settled - store with amount 0 but keep expense history
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $member->user,
                        $item['user'],
                        $item
                    );
                    
                    $result[$member->id]['settled'][$targetGroupMember->id] = [
                        'user' => $item['user'],
                        'is_contact' => false,
                        'amount' => 0,
                        'breakdown' => $breakdown,
                        'expenses' => $item['expenses'] ?? [],
                        'advance' => $item['advance'] ?? 0
                    ];
                    continue;
                }
                
                if ($amount < 0) {
                    // Negative amount means this member owes the other person
                    $owedAmount = abs($amount);
                    
                    // Generate detailed breakdown
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $member->user,
                        $item['user'],
                        $item
                    );
                    
                    $result[$member->id]['owes'][$targetGroupMember->id] = [
                        'user' => $item['user'],
                        'is_contact' => false,
                        'amount' => round($owedAmount, 2),
                        'breakdown' => $breakdown,
                        'expenses' => $item['expenses'] ?? [],
                        'advance' => $item['advance'] ?? 0
                    ];
                } else {
                    // Positive amount means the other person owes this member
                    $owedAmount = abs($amount);
                    
                    // Generate detailed breakdown (from target's perspective)
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $item['user'],
                        $member->user,
                        $item
                    );
                    
                    $result[$targetGroupMember->id]['owes'][$member->id] = [
                        'user' => $member->user,
                        'is_contact' => false,
                        'amount' => round($owedAmount, 2),
                        'breakdown' => $breakdown,
                        'expenses' => $item['expenses'] ?? [],
                        'advance' => $item['advance'] ?? 0
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Calculate optimized settlement suggestions for a group.
     * Returns simplified payment instructions to minimize transactions.
     * Uses the actual settlement matrix to generate suggestions.
     */
    private function calculateSettlementSuggestions(Group $group)
    {
        // Get all settlements first
        $matrix = $this->calculateGroupSettlementMatrix($group);

        $suggestions = [];

        // Extract direct debts from the settlement matrix
        // For each person, if they owe someone, add it to suggestions
        foreach ($matrix as $fromMemberId => $fromData) {
            // Skip contacts - they can't make payments
            if ($fromData['is_contact']) {
                continue;
            }

            $fromUser = $fromData['user'];

            // For each person this member owes
            if (isset($fromData['owes'])) {
                foreach ($fromData['owes'] as $toMemberId => $owesData) {
                    $toUser = $owesData['user'];
                    $amount = $owesData['amount'];

                    // Skip if amount is negligible
                    if ($amount < 0.01) {
                        continue;
                    }

                    $suggestions[] = [
                        'from' => $fromUser->name,
                        'from_id' => $fromUser->id,
                        'to' => $toUser->name,
                        'to_id' => $toUser->id,
                        'amount' => round($amount, 2),
                        'formatted_amount' => number_format(round($amount, 2), 2),
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Display all payments for a user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->get('status', 'all');
        $groupId = $request->get('group_id');

        // Get payments based on filters
        $query = Payment::whereHas('split', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['split.expense.group', 'split.expense.payer', 'split.user', 'attachments']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($groupId) {
            $query->whereHas('split.expense', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        $payments = $query->latest()->paginate(20);

        // Get summary stats
        $stats = $this->paymentService->getPaymentStats($user, $groupId);

        // Get user's groups for filter
        $groups = $user->groups;

        return view('payments.index', compact('payments', 'stats', 'groups', 'status', 'groupId'));
    }

    /**
     * Show payment details.
     */
    public function show(Payment $payment)
    {
        // Check authorization
        $user = auth()->user();
        if ($payment->split->user_id !== $user->id &&
            $payment->split->expense->payer_id !== $user->id &&
            !$payment->split->expense->group->isAdmin($user)) {
            abort(403, 'Unauthorized access to payment');
        }

        $payment->load([
            'split.expense.group',
            'split.expense.payer',
            'split.user',
            'paidBy',
            'attachments'
        ]);

        return view('payments.show', compact('payment'));
    }

    /**
     * Mark a payment as paid (entry point for Payment model binding).
     */
    public function markPayment(Request $request, Payment $payment)
    {
        // Get the split from the payment
        $split = $payment->split;
        return $this->markPaid($request, $split);
    }

    /**
     * Mark a payment as paid.
     */
    public function markPaid(Request $request, ExpenseSplit $split)
    {
        $user = auth()->user();

        // Check authorization - only the person who owes can mark as paid
        if ($split->user_id !== $user->id) {
            abort(403, 'You can only mark your own payments as paid');
        }

        $validated = $request->validate([
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            // Create or update payment
            $payment = $this->paymentService->markAsPaid($split, $user, $validated);

            // Log payment marked as paid
            $expense = $split->expense;
            $group = $expense->group;
            $this->auditService->logSuccess(
                'mark_paid',
                'Payment',
                "Payment of {$payment->amount} marked as paid for '{$expense->title}' in group '{$group->name}'",
                $payment->id,
                $group->id
            );

            // Handle receipt attachment
            if ($request->hasFile('receipt')) {
                $this->attachmentService->uploadAttachment(
                    $request->file('receipt'),
                    $payment,
                    'payments'
                );
            }

            // Notify the payer
            $this->notificationService->notifyPaymentMarked($payment, $user);

            // Check if expense is fully paid
            app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);

            return back()->with('success', 'Payment marked as paid successfully!');
        } catch (\Exception $e) {
            // Log failed payment mark
            $this->auditService->logFailed(
                'mark_paid',
                'Payment',
                'Failed to mark payment as paid',
                $e->getMessage()
            );

            return back()->with('error', 'Failed to mark payment: ' . $e->getMessage());
        }
    }

    /**
     * Mark multiple splits as paid in batch (for settling total balance).
     */
    public function markPaidBatch(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'split_ids' => 'required|array',
            'split_ids.*' => 'exists:expense_splits,id',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $successCount = 0;
        $failedCount = 0;
        $totalAmount = 0;
        $payeeName = '';
        $groupName = '';

        foreach ($validated['split_ids'] as $splitId) {
            $split = ExpenseSplit::find($splitId);

            // Check authorization - only the person who owes can mark as paid
            if ($split->user_id !== $user->id) {
                $failedCount++;
                continue;
            }

            try {
                // Create or update payment
                $payment = $this->paymentService->markAsPaid($split, $user, $validated);
                
                // Track total amount and payer info
                $totalAmount += $split->share_amount;
                if (!$payeeName) {
                    $payeeName = $split->expense->payer->name;
                    $groupName = $split->expense->group->name;
                }

                // Handle receipt attachment (only on first payment)
                if ($successCount === 0 && $request->hasFile('receipt')) {
                    $this->attachmentService->uploadAttachment(
                        $request->file('receipt'),
                        $payment,
                        'payments'
                    );
                }

                // Notify the payer
                $this->notificationService->notifyPaymentMarked($payment, $user);

                // Check if expense is fully paid
                app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                \Log::error("Failed to mark split {$splitId} as paid: " . $e->getMessage());
            }
        }

        // Log batch payment
        if ($successCount > 0) {
            $this->auditService->logSuccess(
                'mark_paid_batch',
                'Payment',
                "Batch payment of \${$totalAmount} marked as paid to {$payeeName} in group '{$groupName}' ({$successCount} splits)",
                null,
                null
            );

            // Notify the payer about the first payment (notifications will be sent for each split)
            // The notifyPaymentMarked is already called for each payment in the loop above
        }

        if ($failedCount > 0) {
            return back()->with('warning', "Marked {$successCount} payments as paid. {$failedCount} failed.");
        }

        return back()->with('success', "Successfully marked {$successCount} payments as paid! Total: \${$totalAmount}");
    }

    /**
     * Manual settlement for balances without specific split_ids (e.g., rounding differences, adjustments).
     */
    public function manualSettle(Request $request, Group $group)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $otherUser = \App\Models\User::findOrFail($validated['user_id']);

        // Create a manual settlement record as an advance payment
        // Advances are created with sent_to_user_id (recipient) and tracked senders via pivot table
        $advance = new \App\Models\Advance();
        $advance->group_id = $group->id;
        $advance->sent_to_user_id = $otherUser->id;  // The person receiving the advance
        $advance->amount_per_person = $validated['amount'];
        $advance->date = now()->toDateString();
        $advance->description = "Manual settlement - clearing balance";
        $advance->save();

        // Add the current user as the sender of this advance
        $advance->senders()->attach($user->id);

        // Log the manual settlement
        $this->auditService->logSuccess(
            'manual_settle',
            'Advance',
            "Manual settlement of \${$validated['amount']} from {$user->name} to {$otherUser->name} in group '{$group->name}'",
            $advance->id,
            $group->id
        );

        return back()->with('success', "Balance of \${$validated['amount']} settled with {$otherUser->name}!");
    }

    /**
     * Approve a payment (for payer/admin).
     */
    public function approve(Request $request, Payment $payment)
    {
        $user = auth()->user();
        $expense = $payment->split->expense;

        // Check authorization - only payer or admin can approve
        if ($expense->payer_id !== $user->id && !$expense->group->isAdmin($user)) {
            abort(403, 'Only the payer or group admin can approve payments');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'notes' => $validated['notes'] ?? $payment->notes,
            ]);

            // Log payment approval
            $group = $expense->group;
            $this->auditService->logSuccess(
                'approve_payment',
                'Payment',
                "Payment of {$payment->amount} from {$payment->split->user->name} for '{$expense->title}' approved in group '{$group->name}'",
                $payment->id,
                $group->id
            );

            // Notify the person who paid
            $this->notificationService->createNotification($payment->split->user, [
                'type' => 'payment_approved',
                'title' => 'Payment Approved',
                'message' => "{$user->name} approved your payment for {$expense->title}",
                'data' => ['payment_id' => $payment->id, 'expense_id' => $expense->id],
            ]);

            // Check if expense is fully paid
            app('App\Services\ExpenseService')->markExpenseAsPaid($expense);

            return back()->with('success', 'Payment approved successfully!');
        } catch (\Exception $e) {
            // Log failed payment approval
            $this->auditService->logFailed(
                'approve_payment',
                'Payment',
                'Failed to approve payment',
                $e->getMessage()
            );

            return back()->with('error', 'Failed to approve payment: ' . $e->getMessage());
        }
    }

    /**
     * Reject a payment.
     */
    public function reject(Request $request, Payment $payment)
    {
        $user = auth()->user();
        $expense = $payment->split->expense;

        // Check authorization - only payer or admin can reject
        if ($expense->payer_id !== $user->id && !$expense->group->isAdmin($user)) {
            abort(403, 'Only the payer or group admin can reject payments');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->paymentService->rejectPayment($payment, $validated['reason']);

            // Log payment rejection
            $group = $expense->group;
            $this->auditService->logSuccess(
                'reject_payment',
                'Payment',
                "Payment of {$payment->amount} from {$payment->split->user->name} for '{$expense->title}' rejected in group '{$group->name}' - Reason: {$validated['reason']}",
                $payment->id,
                $group->id
            );

            // Notify the person who paid
            $this->notificationService->createNotification($payment->split->user, [
                'type' => 'payment_rejected',
                'title' => 'Payment Rejected',
                'message' => "{$user->name} rejected your payment for {$expense->title}",
                'data' => ['payment_id' => $payment->id, 'expense_id' => $expense->id, 'reason' => $validated['reason']],
            ]);

            return back()->with('success', 'Payment rejected. User has been notified.');
        } catch (\Exception $e) {
            // Log failed payment rejection
            $this->auditService->logFailed(
                'reject_payment',
                'Payment',
                'Failed to reject payment',
                $e->getMessage()
            );

            return back()->with('error', 'Failed to reject payment: ' . $e->getMessage());
        }
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(ExpenseSplit $split)
    {
        $user = auth()->user();
        $expense = $split->expense;

        // Check authorization - only payer or admin can send reminders
        if ($expense->payer_id !== $user->id && !$expense->group->isAdmin($user)) {
            abort(403, 'Only the payer or group admin can send reminders');
        }

        try {
            $this->notificationService->sendPaymentReminder($split->user, $expense);

            return back()->with('success', 'Reminder sent to ' . $split->user->name);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send reminder: ' . $e->getMessage());
        }
    }

    /**
     * Bulk mark payments as paid.
     */
    public function bulkMarkPaid(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'split_ids' => 'required|array',
            'split_ids.*' => 'exists:expense_splits,id',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($validated['split_ids'] as $splitId) {
            $split = ExpenseSplit::find($splitId);

            // Check authorization
            if ($split->user_id !== $user->id) {
                $failedCount++;
                continue;
            }

            try {
                $this->paymentService->markAsPaid($split, $user, [
                    'paid_date' => $validated['paid_date'] ?? now()->toDateString(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        $message = "Successfully marked {$successCount} payment(s) as paid.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} payment(s) failed.";
        }

        return back()->with('success', $message);
    }

    /**
     * Calculate expense breakdown by category.
     */
    private function calculateCategoryBreakdown(Group $group): array
    {
        $categoryBreakdown = [];
        $totalByCategory = [];

        foreach ($group->expenses as $expense) {
            $category = $expense->category ?? 'Other';

            if (!isset($totalByCategory[$category])) {
                $totalByCategory[$category] = [
                    'category' => $category,
                    'total' => 0,
                    'count' => 0,
                    'expenses' => []
                ];
            }

            $totalByCategory[$category]['total'] += $expense->amount;
            $totalByCategory[$category]['count'] += 1;
            $totalByCategory[$category]['expenses'][] = [
                'title' => $expense->title,
                'amount' => $expense->amount,
                'payer' => $expense->payer->name,
                'date' => $expense->date,
            ];
        }

        // Sort by total amount (descending)
        uasort($totalByCategory, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $totalByCategory;
    }

    /**
     * Export group payment history as PDF.
     */
    public function exportHistoryPdf(Group $group)
    {
        // Check if user is member of group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Load group with all necessary relationships
        $group->load(['members', 'contacts', 'expenses.splits.user', 'expenses.splits.contact', 'expenses.splits.payment', 'expenses.payer']);

        // Calculate overall settlement matrix for all group members
        $overallSettlement = $this->calculateGroupSettlementMatrix($group);

        // Get complete transaction history for the group
        $transactionHistory = $this->getGroupTransactionHistory($group);

        // Calculate total group expenses
        $totalExpenses = $group->expenses->sum('amount');

        // Calculate category breakdown
        $categoryBreakdown = $this->calculateCategoryBreakdown($group);

        // Generate PDF
        $pdf = Pdf::loadView('groups.payments.history-pdf', [
            'group' => $group,
            'overallSettlement' => $overallSettlement,
            'transactionHistory' => $transactionHistory,
            'totalExpenses' => $totalExpenses,
            'categoryBreakdown' => $categoryBreakdown,
            'exportDate' => now()->format('F d, Y'),
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');

        // Generate filename
        $filename = 'Group_History_' . str_replace(' ', '_', $group->name) . '_' . now()->format('Y-m-d') . '.pdf';
        
        // For Android WebView compatibility, use download with proper headers
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get received payments for a specific member in a group.
     * Shows both payments received FROM and TO the member.
     */
    public function getReceivedPayments(Group $group, User $member)
    {
        // Check if user is member of group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Check if the target member is in the group
        if (!$group->hasMember($member)) {
            abort(404, 'Member not found in this group');
        }

        // Get payments received by this member FROM others
        $receivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('to_user_id', $member->id)
            ->with(['fromUser'])
            ->orderBy('received_date', 'desc')
            ->get();

        // Get payments sent BY this member TO others
        $sentPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('from_user_id', $member->id)
            ->with(['toUser'])
            ->orderBy('received_date', 'desc')
            ->get();

        return view('groups.payments.member-received-payments', compact('group', 'member', 'receivedPayments', 'sentPayments'));
    }

    /**
     * Debug endpoint to analyze settlement calculations and spot discrepancies.
     * Only accessible to group admins for troubleshooting.
     */
    public function debugSettlement(Group $group, User $user)
    {
        // Check admin permission
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only group admins can access debug information');
        }

        $analysis = [];

        // Get all expenses for the group with their splits
        $expenses = $group->expenses()->with(['payer', 'splits.user', 'members'])->get();

        foreach ($expenses as $expense) {
            $totalHeadcount = 0;
            $memberCount = 0;

            // Calculate group's current total headcount
            foreach ($group->members as $member) {
                $familyCount = $member->pivot->family_count ?? 1;
                $totalHeadcount += max(1, $familyCount);
                $memberCount++;
            }

            $expectedPerPersonCost = $expense->amount / $totalHeadcount;

            $expenseAnalysis = [
                'id' => $expense->id,
                'title' => $expense->title,
                'total_amount' => $expense->amount,
                'payer' => $expense->payer->name,
                'split_type' => $expense->split_type,
                'group_member_count' => $memberCount,
                'group_total_headcount' => $totalHeadcount,
                'expected_per_person_cost' => round($expectedPerPersonCost, 2),
                'splits' => [],
                'splits_total' => 0,
                'discrepancies' => []
            ];

            // Analyze each split
            foreach ($expense->splits as $split) {
                $expectedAmount = $expectedPerPersonCost * ($split->user->pivot->family_count ?? 1);
                $actual = $split->share_amount;
                $difference = $actual - $expectedAmount;

                $splitInfo = [
                    'user' => $split->user->name,
                    'family_count' => $split->user->pivot->family_count ?? 1,
                    'actual_split' => $actual,
                    'expected_split' => round($expectedAmount, 2),
                    'difference' => round($difference, 2)
                ];

                if (abs($difference) > 0.01) {
                    $splitInfo['status'] = 'MISMATCH';
                    $expenseAnalysis['discrepancies'][] = $splitInfo;
                }

                $expenseAnalysis['splits'][] = $splitInfo;
                $expenseAnalysis['splits_total'] += $actual;
            }

            // Check if splits total matches expense amount
            $splitsTotalDiff = $expenseAnalysis['splits_total'] - $expense->amount;
            if (abs($splitsTotalDiff) > 0.01) {
                $expenseAnalysis['total_discrepancy'] = $splitsTotalDiff;
                $expenseAnalysis['total_status'] = 'MISMATCH';
            }

            $analysis[] = $expenseAnalysis;
        }

        return response()->json([
            'group' => $group->name,
            'analysis_date' => now(),
            'expenses_analyzed' => count($analysis),
            'details' => $analysis
        ], 200, [], JSON_PRETTY_PRINT);
    }
}
