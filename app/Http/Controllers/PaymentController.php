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
     * Calculate settlement for a user in a group.
     * Returns net balance with each person (positive = user owes, negative = person owes user).
     */
    private function calculateSettlement(Group $group, $user)
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
                        $payerId = $expense->payer_id;
                        if (!isset($netBalances[$payerId])) {
                            $netBalances[$payerId] = [
                                'user' => $expense->payer,
                                'net_amount' => 0,
                                'status' => 'pending',
                                'expenses' => [],
                            ];
                        }
                        $netBalances[$payerId]['net_amount'] += $split->share_amount;
                        $netBalances[$payerId]['expenses'][] = [
                            'title' => $expense->title,
                            'amount' => $split->share_amount,
                            'type' => 'you_owe',  // User owes the payer
                        ];
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
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        // Debug: Log advances found
        \Log::info('Advances found for group ' . $group->id . ': ' . $advances->count());

        foreach ($advances as $advance) {
            // Check if user is a sender of this advance (using loaded collection)
            if ($advance->senders->contains('id', $user->id)) {
                \Log::info('User ' . $user->id . ' sent advance of $' . $advance->amount_per_person . ' to ' . $advance->sent_to_user_id);
                // User sent this advance to someone
                $recipientId = $advance->sent_to_user_id;

                // Skip self-advances (user sending to themselves)
                if ($recipientId !== $user->id) {
                    $advanceAmount = $advance->amount_per_person;

                    if (!isset($netBalances[$recipientId])) {
                        $netBalances[$recipientId] = [
                            'user' => $advance->sentTo,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                        ];
                    }

                    // User paid advance to recipient
                    // This INCREASES what recipient owes to user (makes balance more negative)
                    $netBalances[$recipientId]['net_amount'] -= $advanceAmount;

                    \Log::info('Applied advance: recipient ' . $recipientId . ' now owes ' . $netBalances[$recipientId]['net_amount']);
                }
            }

            // Check if user received an advance from someone
            if ($advance->sent_to_user_id === $user->id) {
                \Log::info('User ' . $user->id . ' received advance of $' . $advance->amount_per_person . ' from senders');
                // Someone sent this advance to the user
                foreach ($advance->senders as $sender) {
                    // Skip if sender is the user themselves
                    if ($sender->id === $user->id) {
                        continue;
                    }

                    $senderId = $sender->id;
                    $advanceAmount = $advance->amount_per_person;

                    if (!isset($netBalances[$senderId])) {
                        $netBalances[$senderId] = [
                            'user' => $sender,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                        ];
                    }

                    // Sender paid advance to user
                    // User owes less to sender (reduces positive balance or increases negative balance)
                    $netBalances[$senderId]['net_amount'] -= $advanceAmount;

                    \Log::info('Applied advance: user now owes sender ' . $senderId . ' amount: ' . $netBalances[$senderId]['net_amount']);
                }
            }
        }

        // Account for received payments
        // These reduce what the user owes to others (payment received FROM them)
        // Payment amount needs to be adjusted based on payer's family count
        $receivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('to_user_id', $user->id)
            ->with(['fromUser'])
            ->get();

        foreach ($receivedPayments as $receivedPayment) {
            $fromUserId = $receivedPayment->from_user_id;

            // If this person is in the settlement, reduce what the user owes them
            if (isset($netBalances[$fromUserId])) {
                // Get the payer's family count to adjust the payment amount
                $payerFamilyCount = $group->members()
                    ->where('user_id', $fromUserId)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;

                if ($payerFamilyCount <= 0) {
                    $payerFamilyCount = 1;
                }

                // Adjust received amount proportionally by payer's family count
                // The received payment is divided by their family count to get per-person credit
                $adjustedAmount = $receivedPayment->amount / $payerFamilyCount;

                // Subtract adjusted payment from what user owes to this person
                $netBalances[$fromUserId]['net_amount'] -= $adjustedAmount;
                $netBalances[$fromUserId]['expenses'][] = [
                    'title' => 'Payment Received',
                    'amount' => $adjustedAmount,
                    'type' => 'payment_received',  // Special type for received payments
                ];
            }
        }

        // Account for payments sent to others
        // These increase what others owe to the user (payment sent TO them)
        // Sent payment needs to be adjusted based on sender's family count
        $sentPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->where('from_user_id', $user->id)
            ->with(['toUser'])
            ->get();

        foreach ($sentPayments as $sentPayment) {
            $toUserId = $sentPayment->to_user_id;

            // If this person is in the settlement, increase what they owe user
            if (isset($netBalances[$toUserId])) {
                // Get the sender's (current user's) family count to adjust the payment amount
                $senderFamilyCount = $group->members()
                    ->where('user_id', $user->id)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;

                if ($senderFamilyCount <= 0) {
                    $senderFamilyCount = 1;
                }

                // Adjust sent amount proportionally by sender's family count
                // The sent payment is divided by their family count to get per-person credit
                $adjustedAmount = $sentPayment->amount / $senderFamilyCount;

                // Subtract from their balance (makes it more negative - they owe more to user)
                $netBalances[$toUserId]['net_amount'] -= $adjustedAmount;
                $netBalances[$toUserId]['expenses'][] = [
                    'title' => 'Payment Sent',
                    'amount' => $adjustedAmount,
                    'type' => 'payment_sent',  // Special type for sent payments
                ];
            }
        }

        // Convert to settlement array, filtering out zero balances
        // Positive net_amount = user owes this person
        // Negative net_amount = this person owes user (we show as positive amount they owe)
        $settlements = [];
        foreach ($netBalances as $personId => $data) {
            if ($data['net_amount'] != 0) {
                // Find all split IDs if this is user owing money to someone
                $splitIds = [];
                if ($data['net_amount'] > 0) {
                    // User owes this person money - find splits for all expenses in the list
                    foreach ($data['expenses'] as $expenseData) {
                        $expenseTitle = $expenseData['title'];
                        $expense = Expense::where('title', $expenseTitle)
                            ->where('group_id', $group->id)
                            ->first();

                        if ($expense) {
                            $split = ExpenseSplit::where('expense_id', $expense->id)
                                ->where('user_id', $user->id)
                                ->first();

                            if ($split) {
                                $splitIds[] = $split->id;
                            }
                        }
                    }
                }

                $settlements[] = [
                    'user' => $data['user'],
                    'amount' => abs($data['net_amount']),  // Final amount after all calculations including advances
                    'net_amount' => $data['net_amount'],  // Positive = user owes, Negative = user is owed
                    'status' => $data['status'],
                    'expenses' => $data['expenses'] ?? [],  // List of expenses contributing to this settlement
                    'split_ids' => $splitIds,  // Split IDs for all expenses in this settlement (used for marking as paid)
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
            ->with(['payer', 'splits.user', 'splits.payment'])
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
        // Build settlement matrix including both users and contacts
        $result = [];

        // Get all members (users and contacts)
        $groupMembers = $group->allMembers()->with(['user', 'contact'])->get();

        // Initialize result for all members
        foreach ($groupMembers as $groupMember) {
            $memberObj = $groupMember->isContact() ? $groupMember->contact : $groupMember->user;
            if (!$memberObj) continue;

            $result[$groupMember->id] = [
                'user' => $memberObj,
                'is_contact' => $groupMember->isContact(),
                'owes' => []
            ];
        }

        // For each USER only (not contacts), calculate their personal settlement
        // Contacts don't have settlements since they can't make payments
        foreach ($group->members as $member) {
            $settlement = $this->calculateSettlement($group, $member);

            // Find the GroupMember ID for this user
            $memberGroupMemberId = null;
            foreach ($result as $gmKey => $gmData) {
                if (!$gmData['is_contact'] && $gmData['user']->id === $member->id) {
                    $memberGroupMemberId = $gmKey;
                    break;
                }
            }

            if (!$memberGroupMemberId) {
                continue; // Skip if GroupMember not found
            }

            // Convert settlement to matrix
            foreach ($settlement as $item) {
                // Get the target person/contact info
                $targetIsContact = isset($item['is_contact']) && $item['is_contact'];
                $amount = $item['net_amount'];

                if ($amount > 0) {
                    // Member owes someone
                    if ($targetIsContact) {
                        // Member owes a contact - find contact in result by matching the contact object

                        $contactId = $item['user']->id;
                        foreach ($result as $key => $entry) {
                            if ($entry['is_contact'] && $entry['user']->id === $contactId) {
                                // Generate breakdown for contact owed
                                $breakdownContact = $result[$memberGroupMemberId]['user']->name . " spent: $" . number_format(abs($item['net_amount']), 2);
                                if (isset($item['expenses']) && count($item['expenses']) > 0) {
                                    foreach ($item['expenses'] as $exp) {
                                        if ($exp['type'] !== 'advance') {
                                            $breakdownContact .= "\n- {$exp['title']}: $" . number_format($exp['amount'], 2);
                                        } else {
                                            $breakdownContact .= "\n- Advance paid: -$" . number_format($exp['amount'], 2);
                                        }
                                    }
                                }
                                $breakdownContact .= "\n\nFinal: $" . number_format(round($amount, 2), 2);

                                $result[$memberGroupMemberId]['owes'][$key] = [
                                    'user' => $item['user'],
                                    'is_contact' => true,
                                    'amount' => round($amount, 2),
                                    'breakdown' => $breakdownContact,
                                ];
                                break;
                            }
                        }
                    } else {
                        // Member owes a user - find the GroupMember ID for this user
                        $targetUserId = $item['user']->id;
                        foreach ($result as $gmKey => $gmData) {
                            if (!$gmData['is_contact'] && $gmData['user']->id === $targetUserId) {
                                $breakdown = "User spent: $" . number_format(abs($item['net_amount']), 2);
                                if (isset($item['expenses']) && count($item['expenses']) > 0) {
                                    foreach ($item['expenses'] as $exp) {
                                        if ($exp['type'] !== 'advance') {
                                            $breakdown .= "\n- {$exp['title']}: $" . number_format($exp['amount'], 2);
                                        } else {
                                            $breakdown .= "\n- Advance paid: -$" . number_format($exp['amount'], 2);
                                        }
                                    }
                                }
                                $breakdown .= "\n\nFinal: $" . number_format(round($amount, 2), 2);

                                $result[$memberGroupMemberId]['owes'][$gmKey] = [
                                    'user' => $item['user'],
                                    'is_contact' => false,
                                    'amount' => round($amount, 2),
                                    'breakdown' => $breakdown,
                                ];
                                break;
                            }
                        }
                    }
                } else if ($amount < 0) {
                    // Someone owes member (negative amount means member is owed money)
                    if ($targetIsContact) {
                        // A contact owes the member (Arun) - find the contact in result and store that Arun is owed money

                        $contactId = $item['user']->id;
                        foreach ($result as $key => $entry) {
                            if ($entry['is_contact'] && $entry['user']->id === $contactId) {
                                // Generate breakdown - the member is owed by this contact
                                $breakdown = $result[$memberGroupMemberId]['user']->name . " spent: $" . number_format(abs($item['net_amount']), 2);
                                if (isset($item['expenses']) && count($item['expenses']) > 0) {
                                    foreach ($item['expenses'] as $exp) {
                                        if ($exp['type'] !== 'advance') {
                                            $breakdown .= "\n- {$exp['title']}: $" . number_format($exp['amount'], 2);
                                        } else {
                                            $breakdown .= "\n- Advance received: -$" . number_format($exp['amount'], 2);
                                        }
                                    }
                                }
                                $breakdown .= "\n\nFinal: $" . number_format(round(abs($amount), 2), 2);

                                // Contact owes member - store in contact's owes array
                                \Log::info('Storing breakdown in owes array', [
                                    'key' => $key,
                                    'memberGroupMemberId' => $memberGroupMemberId,
                                    'breakdown' => $breakdown,
                                    'breakdown_length' => strlen($breakdown)
                                ]);

                                $result[$key]['owes'][$memberGroupMemberId] = [
                                    'user' => $result[$memberGroupMemberId]['user'],
                                    'is_contact' => false,
                                    'amount' => round(abs($amount), 2),
                                    'breakdown' => $breakdown,
                                ];
                                break;
                            }
                        }
                    } else if ($item['user']) {
                        // A user owes the member - find which GroupMember corresponds to this user
                        $targetUserId = $item['user']->id;
                        foreach ($result as $gmKey => $gmData) {
                            // Find if this is the user we're looking for
                            if (!$gmData['is_contact'] && $gmData['user']->id === $targetUserId) {
                                // This user owes the member
                                if (!isset($result[$gmKey]['owes'])) {
                                    $result[$gmKey]['owes'] = [];
                                }

                                // Generate breakdown string - this user owes the current member money
                                $breakdown = $gmData['user']->name . " spent: $" . number_format(abs($item['net_amount']), 2);
                                if (isset($item['expenses']) && count($item['expenses']) > 0) {
                                    foreach ($item['expenses'] as $exp) {
                                        if ($exp['type'] !== 'advance') {
                                            $breakdown .= "\n- {$exp['title']}: $" . number_format($exp['amount'], 2);
                                        } else {
                                            $breakdown .= "\n- Advance received: -$" . number_format($exp['amount'], 2);
                                        }
                                    }
                                }
                                $breakdown .= "\n\nFinal: $" . number_format(round(abs($amount), 2), 2);

                                $result[$gmKey]['owes'][$memberGroupMemberId] = [
                                    'user' => $group->members->find($member->id),
                                    'is_contact' => false,
                                    'amount' => round(abs($amount), 2),
                                    'breakdown' => $breakdown,
                                ];
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Handle contacts who owe users
        // For each user, check their settlement to see if they're owed money by contacts
        foreach ($group->members as $user) {
            $settlement = $this->calculateSettlement($group, $user);

            foreach ($settlement as $item) {
                // If it's a contact and amount is negative (contact owes user)
                if (isset($item['is_contact']) && $item['is_contact'] && $item['net_amount'] < 0) {
                    $contactId = $item['user']->id;
                    // Find the GroupMember for this contact
                    foreach ($result as $gmKey => $gmData) {
                        if ($gmData['is_contact'] && $gmData['user']->id === $contactId) {
                            // Contact owes user - find the GroupMember ID for this user
                            $targetUserId = $user->id;
                            foreach ($result as $targetGmKey => $targetGmData) {
                                if (!$targetGmData['is_contact'] && $targetGmData['user']->id === $targetUserId) {
                                    if (!isset($result[$gmKey]['owes'])) {
                                        $result[$gmKey]['owes'] = [];
                                    }

                                    // Only update if not already set (to preserve breakdown from previous processing)
                                    if (!isset($result[$gmKey]['owes'][$targetGmKey])) {
                                        $result[$gmKey]['owes'][$targetGmKey] = [
                                            'user' => $user,
                                            'is_contact' => false,
                                            'amount' => round(abs($item['net_amount']), 2),
                                        ];
                                    }
                                    break;
                                }
                            }
                            break;
                        }
                    }
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
}
