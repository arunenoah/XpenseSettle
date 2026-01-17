<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\ReceivedPayment;
use App\Models\Group;
use App\Models\User;
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
     * API: Get settlement details for a user in a group
     *
     * @param Request $request HTTP request with group_id parameter
     * @return array API response with settlement details for all members (including settled)
     */
    public function settlementDetails(Request $request)
    {
        $groupId = $request->query('group_id') ?? $request->input('group_id');

        if (!$groupId) {
            return [
                'success' => false,
                'message' => 'group_id parameter is required',
                'status' => 400,
            ];
        }

        $group = Group::find($groupId);
        if (!$group) {
            return [
                'success' => false,
                'message' => 'Group not found',
                'status' => 404,
            ];
        }

        // Check if user is member of group
        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403,
            ];
        }

        $user = auth()->user();

        // Get settlement details for the current user
        $settlements = $this->calculateSettlement($group, $user);

        // Get all group members to include even those with zero settlement
        $allMembers = $group->members()->get();
        $memberIds = $allMembers->pluck('id')->toArray();

        // Create a map of settlements by user ID for easy lookup
        $settlementMap = [];
        foreach ($settlements as $settlement) {
            $settlementMap[$settlement['user']->id] = $settlement;
        }

        // Build comprehensive member list including those not in settlement
        $memberSettlements = [];
        foreach ($allMembers as $member) {
            if ($member->id === $user->id) {
                continue; // Skip current user
            }

            if (isset($settlementMap[$member->id])) {
                // Member has settlement history
                $settlement = $settlementMap[$member->id];
                $memberSettlements[] = [
                    'user_id' => $member->id,
                    'user_name' => $member->name,
                    'user' => $member,
                    'amount' => $settlement['amount'],
                    'net_amount' => $settlement['net_amount'],
                    'status' => $settlement['status'],
                    'is_settled' => abs($settlement['net_amount']) < 0.01,
                    'expenses' => $settlement['expenses'] ?? [],
                    'split_ids' => $settlement['split_ids'] ?? [],
                ];
            } else {
                // Member with no settlement (fully settled from start)
                $memberSettlements[] = [
                    'user_id' => $member->id,
                    'user_name' => $member->name,
                    'user' => $member,
                    'amount' => 0,
                    'net_amount' => 0,
                    'status' => 'settled',
                    'is_settled' => true,
                    'expenses' => [],
                    'split_ids' => [],
                ];
            }
        }

        // Calculate summary totals
        $totalYouOwe = 0;
        $totalOwedToYou = 0;
        foreach ($settlements as $settlement) {
            if ($settlement['net_amount'] > 0) {
                $totalYouOwe += $settlement['net_amount'];
            } else {
                $totalOwedToYou += abs($settlement['net_amount']);
            }
        }

        return [
            'success' => true,
            'data' => [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'members' => $memberSettlements,
                'total_count' => count($memberSettlements),
                'summary' => [
                    'total_you_owe' => round($totalYouOwe, 2),
                    'total_owed_to_you' => round($totalOwedToYou, 2),
                    'net_balance' => round($totalOwedToYou - $totalYouOwe, 2),
                ],
            ],
            'status' => 200,
        ];
    }

    /**
     * API endpoint: Get payment history for a group
     */
    public function apiPaymentHistory(Group $group)
    {
        // Check authorization
        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403
            ];
        }

        try {
            $user = auth()->user();

            // Get payments for this group
            $payments = Payment::whereHas('split.expense', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->whereHas('split', function ($q) use ($user) {
                $q->where('user_id', $user->id)
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
            ->get();

            $paymentsData = $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'split_id' => $payment->split_id,
                    'amount' => round($payment->amount, 2),
                    'status' => $payment->status,
                    'paid_date' => $payment->paid_date,
                    'created_at' => $payment->created_at,
                    'paid_by' => $payment->paidBy ? [
                        'id' => $payment->paidBy->id,
                        'name' => $payment->paidBy->name,
                        'email' => $payment->paidBy->email,
                    ] : null,
                    'expense' => [
                        'id' => $payment->split->expense->id,
                        'title' => $payment->split->expense->title,
                        'amount' => round($payment->split->expense->amount, 2),
                        'payer' => [
                            'id' => $payment->split->expense->payer->id,
                            'name' => $payment->split->expense->payer->name,
                        ],
                    ],
                ];
            })->toArray();

            return [
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'payments_count' => count($paymentsData),
                    'payments' => $paymentsData,
                ],
                'message' => 'Payment history retrieved successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve payment history: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * API endpoint: Mark a single split payment as paid
     */
    public function apiMarkPayment(Request $request)
    {
        $splitId = $request->query('split_id') ?? $request->input('split_id');

        if (!$splitId) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: split_id',
                'status' => 400
            ];
        }

        $split = ExpenseSplit::find($splitId);
        if (!$split) {
            return [
                'success' => false,
                'message' => 'Payment not found',
                'status' => 404
            ];
        }

        $user = auth()->user();

        // Check authorization - only the person who owes can mark as paid
        if ($split->user_id !== $user->id) {
            return [
                'success' => false,
                'message' => 'You can only mark your own payments as paid',
                'status' => 403
            ];
        }

        $validated = $request->validate([
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
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

            // Notify the payer
            $this->notificationService->notifyPaymentMarked($payment, $user);

            // Check if expense is fully paid
            app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);

            return [
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'split_id' => $split->id,
                    'expense_id' => $expense->id,
                    'amount' => round($payment->amount, 2),
                    'status' => $payment->status,
                    'paid_date' => $payment->paid_date,
                    'created_at' => $payment->created_at,
                ],
                'message' => 'Payment marked as paid successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            // Log failed payment mark
            $this->auditService->logFailed(
                'mark_paid',
                'Payment',
                'Failed to mark payment as paid',
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Failed to mark payment: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * API endpoint: Mark multiple payments as paid in batch
     */
    public function apiMarkPaidBatch(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'split_ids' => 'nullable|array',
            'split_ids.*' => 'exists:expense_splits,id',
            'payee_id' => 'nullable|exists:users,id',
            'group_id' => 'nullable|exists:groups,id',
            'payment_amount' => 'nullable|numeric|min:0.01',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $successCount = 0;
        $failedCount = 0;
        $totalAmount = 0;
        $paidSplits = [];

        try {
            // Collect all splits first - filter out null values
            $splitIds = array_filter($validated['split_ids'] ?? [], function($id) { return $id !== null && $id !== ''; });
            foreach ($splitIds as $splitId) {
                $split = ExpenseSplit::find($splitId);
                if (!$split) {
                    $failedCount++;
                    continue;
                }

                // Check authorization - only the person who owes can mark as paid
                if ($split->user_id !== $user->id) {
                    $failedCount++;
                    continue;
                }

                try {
                    $payment = $this->paymentService->markAsPaid($split, $user, [
                        'paid_date' => $validated['paid_date'] ?? now()->toDateString(),
                        'notes' => $validated['notes'],
                    ]);

                    $successCount++;
                    $totalAmount += $split->share_amount;

                    $paidSplits[] = [
                        'split_id' => $split->id,
                        'payment_id' => $payment->id,
                        'amount' => round($split->share_amount, 2),
                        'status' => $payment->status,
                    ];

                    // Check if expense is fully paid
                    app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);

                } catch (\Exception $e) {
                    $failedCount++;
                    \Log::warning("Failed to mark split {$splitId} as paid: " . $e->getMessage());
                }
            }

            // Handle manual settlement if no splits but payee_id provided
            if ($successCount === 0 && !empty($validated['payee_id']) && !empty($validated['group_id']) && !empty($validated['payment_amount'])) {
                $payeeId = $validated['payee_id'];
                $groupId = $validated['group_id'];
                $amount = $validated['payment_amount'];

                $payment = ReceivedPayment::create([
                    'group_id' => $groupId,
                    'from_user_id' => $user->id,
                    'to_user_id' => $payeeId,
                    'amount' => $amount,
                    'received_date' => $validated['paid_date'] ?? now()->toDateString(),
                    'description' => $validated['notes'] ?? null,
                ]);

                $this->auditService->logSuccess(
                    'manual_settlement',
                    'Payment',
                    "Manual settlement of \${$amount} to " . \App\Models\User::find($payeeId)->name,
                    null,
                    $groupId
                );

                $successCount = 1;
                $totalAmount = $amount;
                $paidSplits[] = [
                    'payment_id' => $payment->id,
                    'amount' => round($amount, 2),
                    'type' => 'manual_settlement',
                ];
            }

            if ($successCount === 0 && $failedCount === 0) {
                return [
                    'success' => false,
                    'message' => 'No payment information provided',
                    'status' => 400,
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'total_amount' => round($totalAmount, 2),
                    'paid_splits' => $paidSplits,
                ],
                'message' => "Successfully marked {$successCount} payment(s) as paid" . ($failedCount > 0 ? " ({$failedCount} failed)" : ''),
                'status' => $successCount > 0 ? 200 : 400,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to process batch payments: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * API endpoint: Get recent activities/transaction history for a group
     */
    public function apiRecentActivities(Request $request)
    {
        $groupId = $request->query('group_id') ?? $request->input('group_id');
        $limit = $request->query('limit') ?? $request->input('limit') ?? 50;

        if (!$groupId) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: group_id',
                'status' => 400
            ];
        }

        $group = Group::find($groupId);
        if (!$group) {
            return [
                'success' => false,
                'message' => 'Group not found',
                'status' => 404
            ];
        }

        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403
            ];
        }

        try {
            $transactions = $this->getGroupTransactionHistory($group);

            // Limit the results
            $transactions = array_slice($transactions, 0, $limit);

            return [
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'total_activities' => count($transactions),
                    'activities' => $transactions,
                ],
                'status' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve activities: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * API endpoint: Get comprehensive transaction history (expenses, advances, payments) filtered by date range
     */
    public function apiTransactionHistory(Request $request)
    {
        $groupId = $request->query('group_id') ?? $request->input('group_id');
        $fromDate = $request->query('from_date') ?? $request->input('from_date');
        $toDate = $request->query('to_date') ?? $request->input('to_date');

        if (!$groupId) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: group_id',
                'status' => 400
            ];
        }

        $group = Group::find($groupId);
        if (!$group) {
            return [
                'success' => false,
                'message' => 'Group not found',
                'status' => 404
            ];
        }

        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403
            ];
        }

        try {
            $query = new \DateTime();
            $fromDateTime = $fromDate ? new \DateTime($fromDate) : null;
            $toDateTime = $toDate ? new \DateTime($toDate) : null;

            // If no to_date provided, use today
            if (!$toDateTime) {
                $toDateTime = new \DateTime();
            }

            // Build transactions array
            $transactions = [];

            // 1. Get all expenses in the group within date range
            $expenses = Expense::where('group_id', $group->id)
                ->when($fromDateTime, function ($q) use ($fromDateTime) {
                    return $q->where('date', '>=', $fromDateTime->format('Y-m-d'));
                })
                ->when($toDateTime, function ($q) use ($toDateTime) {
                    return $q->where('date', '<=', $toDateTime->format('Y-m-d'));
                })
                ->with(['payer', 'splits.user', 'splits.contact'])
                ->latest('date')
                ->get();

            foreach ($expenses as $expense) {
                $transactions[] = [
                    'type' => 'expense',
                    'id' => $expense->id,
                    'date' => $expense->date,
                    'created_at' => $expense->created_at,
                    'title' => $expense->title,
                    'description' => $expense->description,
                    'amount' => round($expense->amount, 2),
                    'category' => $expense->category,
                    'payer' => [
                        'id' => $expense->payer->id,
                        'name' => $expense->payer->name,
                        'email' => $expense->payer->email,
                    ],
                    'split_type' => $expense->split_type,
                    'splits' => $expense->splits->map(function ($split) {
                        $participant = $split->user ?? $split->contact;
                        return [
                            'id' => $split->id,
                            'participant_id' => $participant->id,
                            'participant_name' => $participant->name,
                            'share_amount' => round($split->share_amount, 2),
                            'payment_status' => $split->payment ? $split->payment->status : 'pending',
                        ];
                    })->toArray(),
                ];
            }

            // 2. Get all payments in the group within date range
            $payments = Payment::whereHas('split.expense', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
                ->when($fromDateTime, function ($q) use ($fromDateTime) {
                    // Properly group date conditions with parentheses
                    return $q->where(function ($query) use ($fromDateTime) {
                        $query->where('paid_date', '>=', $fromDateTime->format('Y-m-d'))
                            ->orWhere('created_at', '>=', $fromDateTime);
                    });
                })
                ->when($toDateTime, function ($q) use ($toDateTime) {
                    // Properly group date conditions with parentheses
                    return $q->where(function ($query) use ($toDateTime) {
                        $query->where('paid_date', '<=', $toDateTime->format('Y-m-d'))
                            ->orWhere('created_at', '<=', $toDateTime);
                    });
                })
                ->with(['split.user', 'split.expense', 'paidBy'])
                ->latest('paid_date')
                ->get();

            foreach ($payments as $payment) {
                $transactions[] = [
                    'type' => 'payment',
                    'id' => $payment->id,
                    'date' => $payment->paid_date ?? $payment->created_at->format('Y-m-d'),
                    'created_at' => $payment->created_at,
                    'amount' => round($payment->split->share_amount, 2),
                    'status' => $payment->status,
                    'paid_by' => $payment->paidBy ? [
                        'id' => $payment->paidBy->id,
                        'name' => $payment->paidBy->name,
                    ] : null,
                    'from_user' => [
                        'id' => $payment->split->user->id,
                        'name' => $payment->split->user->name,
                    ],
                    'for_expense' => [
                        'id' => $payment->split->expense->id,
                        'title' => $payment->split->expense->title,
                        'payer' => [
                            'id' => $payment->split->expense->payer->id,
                            'name' => $payment->split->expense->payer->name,
                        ],
                    ],
                ];
            }

            // 3. Get all advances in the group within date range
            $advances = \App\Models\Advance::where('group_id', $group->id)
                ->when($fromDateTime, function ($q) use ($fromDateTime) {
                    // Properly group date conditions with parentheses
                    return $q->where(function ($query) use ($fromDateTime) {
                        $query->where('date', '>=', $fromDateTime->format('Y-m-d'))
                            ->orWhere('created_at', '>=', $fromDateTime);
                    });
                })
                ->when($toDateTime, function ($q) use ($toDateTime) {
                    // Properly group date conditions with parentheses
                    return $q->where(function ($query) use ($toDateTime) {
                        $query->where('date', '<=', $toDateTime->format('Y-m-d'))
                            ->orWhere('created_at', '<=', $toDateTime);
                    });
                })
                ->with(['sentTo', 'senders'])
                ->latest('date')
                ->get();

            foreach ($advances as $advance) {
                $senders = $advance->senders()->get();
                $totalAmount = $advance->getTotalAmount();

                $transactions[] = [
                    'type' => 'advance',
                    'id' => $advance->id,
                    'date' => $advance->date ? $advance->date->format('Y-m-d') : $advance->created_at->format('Y-m-d'),
                    'created_at' => $advance->created_at,
                    'amount_per_person' => round($advance->amount_per_person, 2),
                    'total_amount' => round($totalAmount, 2),
                    'description' => $advance->description,
                    'given_by' => $senders->map(function ($sender) {
                        return [
                            'id' => $sender->id,
                            'name' => $sender->name,
                        ];
                    })->toArray(),
                    'given_to' => [
                        'id' => $advance->sentTo->id,
                        'name' => $advance->sentTo->name,
                    ],
                ];
            }

            // Sort all transactions by date (most recent first)
            usort($transactions, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Calculate summary
            $totalExpenses = array_reduce(
                array_filter($transactions, fn($t) => $t['type'] === 'expense'),
                fn($carry, $t) => $carry + $t['amount'],
                0
            );

            $totalPayments = array_reduce(
                array_filter($transactions, fn($t) => $t['type'] === 'payment'),
                fn($carry, $t) => $carry + $t['amount'],
                0
            );

            $totalAdvances = array_reduce(
                array_filter($transactions, fn($t) => $t['type'] === 'advance'),
                fn($carry, $t) => $carry + $t['total_amount'],
                0
            );

            return [
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'currency' => $group->currency ?? 'USD',
                    'date_range' => [
                        'from' => $fromDate ?? 'earliest',
                        'to' => $toDate ?? date('Y-m-d'),
                    ],
                    'summary' => [
                        'total_expenses' => round($totalExpenses, 2),
                        'total_payments' => round($totalPayments, 2),
                        'total_advances' => round($totalAdvances, 2),
                        'net_balance' => round($totalAdvances - $totalPayments, 2),
                    ],
                    'transactions_count' => count($transactions),
                    'transactions' => $transactions,
                ],
                'message' => 'Transaction history retrieved successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve transaction history: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
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

                        // Only add to net_amount if payment is not marked as paid
                        // Marked payments should not contribute to the settlement balance
                        if (!$payment || $payment->status !== 'paid') {
                            // Add to expenses for visibility and settlement calculation
                            $netBalances[$payerId]['expenses'][] = [
                                'title' => $expense->title,
                                'amount' => $split->share_amount,
                                'type' => 'you_owe',  // User owes the payer
                            ];

                            // Add to net_amount for accurate settlement balance
                            $netBalances[$payerId]['net_amount'] += $split->share_amount;

                            // Track split IDs for unpaid items (for marking as paid)
                            $netBalances[$payerId]['split_ids'][] = $split->id;
                        }
                    }
                } elseif ($expense->payer_id === $user->id && $split->user_id && $split->user_id !== $user->id) {
                    // User is the payer, someone else (a user, not contact) is a participant
                    $payment = $split->payment;

                    $memberId = $split->user_id;
                    if (!isset($netBalances[$memberId])) {
                        $netBalances[$memberId] = [
                            'user' => $split->user,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                            'split_ids' => [],
                        ];
                    }

                    // Only add to net_amount if payment is not marked as paid
                    // Marked payments should not contribute to the settlement balance
                    if (!$payment || $payment->status !== 'paid') {
                        // Add to expenses for visibility and settlement calculation
                        $netBalances[$memberId]['expenses'][] = [
                            'title' => $expense->title,
                            'amount' => $split->share_amount,
                            'type' => 'they_owe',  // Member owes the user (who paid)
                        ];

                        // Subtract from net_amount for accurate settlement balance
                        $netBalances[$memberId]['net_amount'] -= $split->share_amount;
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

                // Payment sent TO someone settles the balance
                // - If positive (user owes): subtract to reduce debt
                // - If negative (they owe user): add to reduce their owed amount
                // This always moves the balance towards zero
                if ($netBalances[$toUserId]['net_amount'] >= 0) {
                    // User owes them: subtract to reduce debt
                    // Example: +284.52 (user owes) - 334.64 (payment sent) = -50.12
                    $netBalances[$toUserId]['net_amount'] -= $amount;
                } else {
                    // They owe user (negative): add to reduce what they owe
                    // Example: -284.52 (they owe user) + 100 (payment sent to settle) = -184.52
                    $netBalances[$toUserId]['net_amount'] += $amount;
                }

                // Add to expenses array so it shows in breakdown
                $netBalances[$toUserId]['expenses'][] = [
                    'title' => 'Payment sent',
                    'amount' => $amount,
                    'type' => 'payment_sent',
                ];
            }
        }

        // Convert to settlement array
        // Include all relationships: non-zero balances AND zero-balance settled relationships
        // Positive net_amount = user owes this person
        // Negative net_amount = this person owes user (we show as positive amount they owe)
        $settlements = [];
        foreach ($netBalances as $personId => $data) {
            // Skip contact entries - they should not appear in user's settlement list
            // The group settlement matrix handles contacts separately
            if (!empty($data['is_contact']) && $data['is_contact'] === true) {
                continue;
            }

            // Always include settlements with non-zero balances
            // Also include zero-balance entries if they have payment history (meaning they've been settled)
            $hasPaymentHistory = !empty($data['expenses']);

            if ($data['net_amount'] != 0 || $hasPaymentHistory) {
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
        // Key format: "groupMemberId1-groupMemberId2" where ID1 < ID2
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

                // Check if we've already processed this exact pair relationship
                // We need to track the direction to avoid duplicate entries in the same cell
                $directedKey = "{$member->id}-{$targetGroupMember->id}";
                if (isset($processedPairs[$directedKey])) continue;

                // Mark this specific direction as processed
                $processedPairs[$directedKey] = true;

                // Store settled pairs (amount = 0) with their expense history
                if (abs($amount) < 0.01) {
                    // Fully settled - store with amount 0 but keep expense history
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $member->user,
                        $item['user'],
                        $item
                    );

                    // Normalize expenses for settled pairs (keep as-is for member's perspective)
                    $normalizedExpenses = [];
                    foreach ($item['expenses'] ?? [] as $exp) {
                        $normalizedExpenses[] = $exp;
                    }

                    $result[$member->id]['settled'][$targetGroupMember->id] = [
                        'user' => $item['user'],
                        'is_contact' => false,
                        'amount' => 0,
                        'breakdown' => $breakdown,
                        'expenses' => $normalizedExpenses,
                        'advance' => $item['advance'] ?? 0
                    ];
                    continue;
                }

                if ($amount > 0) {
                    // Positive amount means this member owes the other person
                    $owedAmount = abs($amount);

                    // Generate detailed breakdown
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $member->user,
                        $item['user'],
                        $item
                    );

                    // Normalize expenses: invert types based on direction
                    // From $member->user's perspective with $item['user']
                    $normalizedExpenses = [];
                    foreach ($item['expenses'] ?? [] as $exp) {
                        $normalized = $exp;
                        // Keep types as-is for member's perspective (they're already calculated correctly)
                        $normalizedExpenses[] = $normalized;
                    }

                    $result[$member->id]['owes'][$targetGroupMember->id] = [
                        'user' => $item['user'],
                        'is_contact' => false,
                        'amount' => round($owedAmount, 2),
                        'breakdown' => $breakdown,
                        'expenses' => $normalizedExpenses,
                        'advance' => $item['advance'] ?? 0
                    ];
                } else {
                    // Negative amount means the other person owes this member
                    $owedAmount = abs($amount);

                    // Generate detailed breakdown (from target's perspective)
                    $breakdown = $this->generateSettlementBreakdown(
                        $group,
                        $item['user'],
                        $member->user,
                        $item
                    );

                    // Normalize expenses: invert types from target's perspective
                    // From $targetGroupMember->user's perspective with $member->user
                    $normalizedExpenses = [];
                    foreach ($item['expenses'] ?? [] as $exp) {
                        $normalized = $exp;
                        // Invert the type because we're switching perspectives
                        if (isset($normalized['type'])) {
                            if ($normalized['type'] === 'you_owe') {
                                $normalized['type'] = 'they_owe';
                            } elseif ($normalized['type'] === 'they_owe') {
                                $normalized['type'] = 'you_owe';
                            }
                        }
                        $normalizedExpenses[] = $normalized;
                    }

                    $result[$targetGroupMember->id]['owes'][$member->id] = [
                        'user' => $member->user,
                        'is_contact' => false,
                        'amount' => round($owedAmount, 2),
                        'breakdown' => $breakdown,
                        'expenses' => $normalizedExpenses,
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

        \Log::info("markPaidBatch called", [
            'user_id' => $user->id,
            'split_ids' => $request->input('split_ids'),
            'payee_id' => $request->input('payee_id'),
            'group_id' => $request->input('group_id'),
            'payment_amount' => $request->input('payment_amount'),
        ]);

        $validated = $request->validate([
            'split_ids' => 'array',
            'split_ids.*' => 'exists:expense_splits,id',
            'payee_id' => 'nullable|exists:users,id',
            'group_id' => 'nullable|exists:groups,id',
            'payment_amount' => 'nullable|numeric|min:0.01',
            'paid_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $successCount = 0;
        $failedCount = 0;
        $totalAmount = 0;
        $payeeName = '';
        $groupIds = [];
        $splits = [];

        // Collect all splits first (if any) - filter out null values
        $splitIds = array_filter($validated['split_ids'] ?? [], function($id) { return $id !== null && $id !== ''; });
        foreach ($splitIds as $splitId) {
            $split = ExpenseSplit::find($splitId);
            if (!$split) continue;

            // Check authorization - both the payer and the person who owes can mark as paid
            // Either: person owes the split ($split->user_id) or person is the payer ($split->expense->payer_id)
            $isOwer = $split->user_id === $user->id;
            $isPayer = $split->expense->payer_id === $user->id;

            if (!$isOwer && !$isPayer) {
                $failedCount++;
                continue;
            }

            $splits[] = $split;
            $totalAmount += $split->share_amount;
            $groupIds[] = $split->expense->group_id;

            if (!$payeeName) {
                $payeeName = $split->expense->payer->name;
            }
        }

        // Handle manual settlement if no splits but payee_id provided
        if (empty($splits) && !empty($validated['payee_id']) && !empty($validated['group_id']) && !empty($validated['payment_amount'])) {
            $payeeId = $validated['payee_id'];
            $groupId = $validated['group_id'];
            $amount = $validated['payment_amount'];

            \Log::info("Creating manual settlement: payee=$payeeId, group=$groupId, amount=$amount");

            try {
                // Create ReceivedPayment for manual settlement
                // Semantics: from_user_id = payer, to_user_id = receiver
                // When Arun (user) pays Mohan (payee), we record: from=Arun, to=Mohan
                $payment = ReceivedPayment::create([
                    'group_id' => $groupId,
                    'from_user_id' => $user->id,   // Person who sends payment (Arun)
                    'to_user_id' => $payeeId,      // Person who receives payment (Mohan)
                    'amount' => $amount,
                    'received_date' => $validated['paid_date'] ?? now()->toDateString(),
                    'description' => $validated['notes'] ?? null,
                ]);

                \Log::info("Manual settlement created: ID=" . $payment->id);

                try {
                    $this->auditService->logSuccess(
                        'manual_settlement',
                        'Payment',
                        "Manual settlement of \${$amount} to " . User::find($payeeId)->name,
                        null,
                        $groupId
                    );
                } catch (\Exception $auditError) {
                    \Log::warning("Audit log failed but settlement was created: " . $auditError->getMessage());
                    // Don't fail the whole operation if audit logging fails
                }

                // Return appropriate response based on request type
                // Check for AJAX request by looking for XMLHttpRequest header
                $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson();

                \Log::info("Manual settlement success, returning response", [
                    'amount' => $amount,
                    'isAjax' => $isAjax,
                    'wantsJson' => $request->wantsJson(),
                    'x-requested-with' => $request->header('X-Requested-With'),
                    'timestamp' => now()->toIso8601String(),
                ]);
                if ($isAjax) {
                    \Log::info("Sending JSON response for manual settlement (success)");
                    return response()->json(['success' => true, 'message' => "Settlement payment of \${$amount} recorded successfully!"]);
                }
                \Log::info("Sending redirect response for manual settlement (success)");
                return back()->with('success', "Settlement payment of \${$amount} recorded successfully!");
            } catch (\Exception $e) {
                \Log::error("Failed to create manual settlement: " . $e->getMessage(), ['exception' => $e]);
                $message = 'Failed to record settlement payment: ' . $e->getMessage();

                $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson();

                \Log::info("Manual settlement failed, returning error response", [
                    'isAjax' => $isAjax,
                    'wantsJson' => $request->wantsJson(),
                    'x-requested-with' => $request->header('X-Requested-With'),
                    'timestamp' => now()->toIso8601String(),
                ]);
                if ($isAjax) {
                    \Log::info("Sending JSON response for manual settlement (error)");
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                \Log::info("Sending redirect response for manual settlement (error)");
                return back()->with('error', $message);
            }
        }

        // If no splits and no manual settlement data
        if (empty($splits)) {
            return back()->with('error', 'No payment information provided.');
        }

        // Determine if this is a multi-group settlement
        $uniqueGroupIds = array_unique($groupIds);
        $isMultiGroupSettlement = count($uniqueGroupIds) > 1;

        if ($isMultiGroupSettlement) {
            // For multi-group settlements, create a ReceivedPayment to settle the net balance
            $primaryGroupId = reset($uniqueGroupIds);
            $primaryGroup = Group::find($primaryGroupId);
            $payeeId = null;

            // Get the payee (person who will receive the payment)
            if (!empty($splits)) {
                $payeeId = $splits[0]->expense->payer_id;
            }

            if ($primaryGroup && $payeeId) {
                try {
                    // Create ReceivedPayment record for settlement
                    ReceivedPayment::create([
                        'group_id' => $primaryGroupId,
                        'from_user_id' => $user->id,
                        'to_user_id' => $payeeId,
                        'amount' => $totalAmount,
                        'paid_date' => $validated['paid_date'] ?? now()->toDateString(),
                        'notes' => $validated['notes'] ?? null,
                    ]);

                    // Also mark individual splits as paid
                    foreach ($splits as $split) {
                        try {
                            $payment = $this->paymentService->markAsPaid($split, $user, $validated);

                            // Handle receipt attachment (only on first payment)
                            if ($successCount === 0 && $request->hasFile('receipt')) {
                                try {
                                    $this->attachmentService->uploadAttachment(
                                        $request->file('receipt'),
                                        $payment,
                                        'payments'
                                    );
                                } catch (\Exception $attachmentError) {
                                    \Log::warning("Failed to attach receipt: " . $attachmentError->getMessage());
                                }
                            }

                            // Notify the payer (wrap in try-catch to not interrupt payment processing)
                            try {
                                $this->notificationService->notifyPaymentMarked($payment, $user);
                            } catch (\Exception $notifyError) {
                                \Log::warning("Failed to notify payer: " . $notifyError->getMessage());
                            }

                            // Check if expense is fully paid
                            try {
                                app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);
                            } catch (\Exception $expenseError) {
                                \Log::warning("Failed to mark expense as paid: " . $expenseError->getMessage());
                            }

                            $successCount++;
                        } catch (\Exception $e) {
                            $failedCount++;
                            \Log::error("Failed to mark split {$split->id} as paid: " . $e->getMessage());
                        }
                    }

                    // Log batch settlement payment
                    $this->auditService->logSuccess(
                        'mark_paid_batch_multi_group',
                        'Payment',
                        "Settlement payment of \${$totalAmount} from {$user->name} to {$payeeName} across " . count($uniqueGroupIds) . " groups ({$successCount} splits)",
                        null,
                        $primaryGroupId
                    );

                    if ($failedCount > 0) {
                        $msg = "Marked {$successCount} payments as paid across " . count($uniqueGroupIds) . " groups. {$failedCount} failed.";
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $msg], 200);
                        }
                        return back()->with('warning', $msg);
                    }

                    $msg = "Successfully settled \${$totalAmount} with {$payeeName} across " . count($uniqueGroupIds) . " groups!";
                    if ($request->wantsJson()) {
                        return response()->json(['success' => true, 'message' => $msg]);
                    }
                    return back()->with('success', $msg);
                } catch (\Exception $e) {
                    \Log::error("Failed to create multi-group settlement: " . $e->getMessage());
                    $message = 'Failed to process multi-group settlement: ' . $e->getMessage();
                    if ($request->wantsJson()) {
                        return response()->json(['success' => false, 'message' => $message], 422);
                    }
                    return back()->with('error', $message);
                }
            }
        } else {
            // Single group settlement - mark splits as paid normally
            foreach ($splits as $split) {
                try {
                    // Create or update payment
                    $payment = $this->paymentService->markAsPaid($split, $user, $validated);

                    // Track total amount and payer info
                    if (!isset($groupName)) {
                        $groupName = $split->expense->group->name;
                    }

                    // Handle receipt attachment (only on first payment)
                    if ($successCount === 0 && $request->hasFile('receipt')) {
                        try {
                            $this->attachmentService->uploadAttachment(
                                $request->file('receipt'),
                                $payment,
                                'payments'
                            );
                        } catch (\Exception $attachmentError) {
                            \Log::warning("Failed to attach receipt: " . $attachmentError->getMessage());
                            // Don't fail the payment if attachment fails
                        }
                    }

                    // Notify the payer (wrap in try-catch to not interrupt payment processing)
                    try {
                        $this->notificationService->notifyPaymentMarked($payment, $user);
                    } catch (\Exception $notifyError) {
                        \Log::warning("Failed to notify payer: " . $notifyError->getMessage());
                        // Don't fail the payment if notification fails
                    }

                    // Check if expense is fully paid
                    try {
                        app('App\Services\ExpenseService')->markExpenseAsPaid($split->expense);
                    } catch (\Exception $expenseError) {
                        \Log::warning("Failed to mark expense as paid: " . $expenseError->getMessage());
                        // Don't fail the payment if expense marking fails
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    \Log::error("Failed to mark split {$split->id} as paid: " . $e->getMessage());
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
            }

            // Check if this is an AJAX request by looking for XMLHttpRequest header
            // (wantsJson() may not work reliably with multipart/form-data uploads)
            $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || $request->wantsJson();

            if ($failedCount > 0) {
                $msg = "Marked {$successCount} payments as paid. {$failedCount} failed.";
                \Log::info("markPaidBatch returning warning response", [
                    'isAjax' => $isAjax,
                    'wantsJson' => $request->wantsJson(),
                    'x-requested-with' => $request->header('X-Requested-With'),
                    'message' => $msg,
                    'timestamp' => now()->toIso8601String(),
                ]);
                if ($isAjax) {
                    \Log::info("Sending JSON response (warning)");
                    return response()->json(['success' => false, 'message' => $msg], 200);
                }
                \Log::info("Sending redirect response (warning)");
                return back()->with('warning', $msg);
            }

            $msg = "Successfully marked {$successCount} payments as paid! Total: \${$totalAmount}";
            \Log::info("markPaidBatch returning success response", [
                'isAjax' => $isAjax,
                'wantsJson' => $request->wantsJson(),
                'x-requested-with' => $request->header('X-Requested-With'),
                'message' => $msg,
                'successCount' => $successCount,
                'totalAmount' => $totalAmount,
                'timestamp' => now()->toIso8601String(),
            ]);
            if ($isAjax) {
                \Log::info("Sending JSON response (success)");
                return response()->json(['success' => true, 'message' => $msg]);
            }
            \Log::info("Sending redirect response (success)");
            return back()->with('success', $msg);
        }
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
     * Export individual settlement reports for all members in a group as a PDF.
     * Shows detailed breakdown for each member: expenses paid, expenses participated in,
     * advances, payments, and net amount owed to other members.
     */
    public function exportMemberSettlementsPdf(Group $group)
    {
        // Check if user is member of group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Load group with all necessary relationships
        $group->load(['members', 'contacts', 'expenses.splits.user', 'expenses.splits.contact', 'expenses.splits.payment', 'expenses.payer']);

        // Get all group members (members() returns User objects directly)
        $members = $group->members;

        // Calculate settlement details for each member
        $memberSettlements = [];
        foreach ($members as $user) {
            // $members contains User objects directly from belongsToMany relationship
            $settlement = $this->calculateSettlement($group, $user);

            // Get received payments for this member
            $receivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
                ->where('to_user_id', $user->id)
                ->orderBy('received_date', 'desc')
                ->get();

            // Calculate total owed to each member
            $totalOwedByMember = 0;
            $detailedOwings = [];
            $totalPaidAmount = 0;
            $totalParticipatedAmount = 0;

            foreach ($settlement as $otherId => $settleData) {
                $amount = (float)$settleData['net_amount'];
                if ($amount > 0 && isset($settleData['user'])) {
                    $totalOwedByMember += $amount;
                    // Only add to detailed owings if amount is greater than 0
                    if ($amount > 0) {
                        $detailedOwings[$otherId] = [
                            'name' => $settleData['user']->name,
                            'amount' => $amount,
                        ];
                    }
                }

                // Calculate what they paid vs what they owe
                if (!empty($settleData['expenses'])) {
                    foreach ($settleData['expenses'] as $exp) {
                        if ($exp['type'] === 'they_owe') {
                            $totalPaidAmount += $exp['amount'];
                        } else {
                            $totalParticipatedAmount += $exp['amount'];
                        }
                    }
                }
            }

            $memberSettlements[$user->id] = [
                'user' => $user,
                'settlement' => $settlement,
                'receivedPayments' => $receivedPayments,
                'totalOwed' => $totalOwedByMember,
                'detailedOwings' => $detailedOwings,
                'totalPaidAmount' => $totalPaidAmount,
                'totalParticipatedAmount' => $totalParticipatedAmount,
            ];
        }

        try {
            // Generate PDF
            $pdf = Pdf::loadView('groups.payments.member-settlements-pdf', [
                'group' => $group,
                'memberSettlements' => $memberSettlements,
                'exportDate' => now()->format('F d, Y'),
            ]);

            // Set PDF options
            $pdf->setPaper('a4', 'portrait');

            // Generate filename
            $filename = 'Member_Settlements_' . str_replace(' ', '_', $group->name) . '_' . now()->format('Y-m-d') . '.pdf';

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
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage(), [
                'exception' => $e,
                'group_id' => $group->id,
            ]);
            abort(500, 'Failed to generate PDF: ' . $e->getMessage());
        }
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
