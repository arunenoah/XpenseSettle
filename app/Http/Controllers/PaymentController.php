<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\Group;
use App\Services\AttachmentService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private AttachmentService $attachmentService;
    private NotificationService $notificationService;

    public function __construct(
        PaymentService $paymentService,
        AttachmentService $attachmentService,
        NotificationService $notificationService
    ) {
        $this->paymentService = $paymentService;
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
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
            'expenses.splits.payment',
            'expenses.payer',
            'members'
        ]);

        // Check if user is admin
        $isAdmin = $group->isAdmin($user);

        // Always calculate user's personal settlement
        $personalSettlement = $this->calculateSettlement($group, $user);

        // For admins, also calculate overall settlement matrix
        $overallSettlement = $isAdmin ? $this->calculateGroupSettlementMatrix($group) : [];

        return view('groups.payments.history', compact('group', 'payments', 'personalSettlement', 'overallSettlement', 'isAdmin'));
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
                    $q->with(['payment', 'user']);
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
            if ($expense->payer_id === $user->id && $expense->splits->count() === 1 && $expense->splits->first()->user_id === $user->id) {
                continue;
            }

            // Handle regular splits (equal, custom)
            foreach ($expense->splits as $split) {
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
                } elseif ($expense->payer_id === $user->id && $split->user_id !== $user->id) {
                    // User is the payer, someone else is a participant
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
                }
            }
        }

        // Account for advances
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        foreach ($advances as $advance) {
            // Check if user is a sender of this advance (using loaded collection)
            if ($advance->senders->contains('id', $user->id)) {
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

                    // Advance payment reduces what recipient owes
                    // If net_amount is positive (user owes recipient): subtract to reduce debt
                    // If net_amount is negative (recipient owes user): add to reduce their debt
                    if ($netBalances[$recipientId]['net_amount'] >= 0) {
                        $netBalances[$recipientId]['net_amount'] -= $advanceAmount;
                    } else {
                        // Negative amount: adding makes it less negative (reduces what recipient owes)
                        $netBalances[$recipientId]['net_amount'] += $advanceAmount;
                    }
                }
            }

            // Check if user received an advance from someone
            if ($advance->sent_to_user_id === $user->id) {
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

                    // Sender paid advance to user - reduces what user owes to sender
                    // If net_amount is positive (user owes sender): subtract to reduce debt
                    // If net_amount is negative (sender owes user): add to reduce what sender owes
                    if ($netBalances[$senderId]['net_amount'] >= 0) {
                        $netBalances[$senderId]['net_amount'] -= $advanceAmount;
                    } else {
                        // Negative amount: adding makes it less negative (reduces what sender owes)
                        $netBalances[$senderId]['net_amount'] += $advanceAmount;
                    }
                }
            }
        }

        // Convert to settlement array, filtering out zero balances
        // Positive net_amount = user owes this person
        // Negative net_amount = this person owes user (we show as positive amount they owe)
        $settlements = [];
        foreach ($netBalances as $personId => $data) {
            if ($data['net_amount'] != 0) {
                // Find all payment IDs if this is user owing money to someone
                $paymentIds = [];
                if ($data['net_amount'] > 0) {
                    // User owes this person money - find payments for all expenses in the list
                    foreach ($data['expenses'] as $expenseData) {
                        $expenseTitle = $expenseData['title'];
                        $expense = Expense::where('title', $expenseTitle)
                            ->where('group_id', $group->id)
                            ->first();

                        if ($expense) {
                            $payment = Payment::whereHas('split', function ($q) use ($user, $expense) {
                                $q->where('user_id', $user->id)
                                  ->where('expense_id', $expense->id);
                            })->first();

                            if ($payment) {
                                $paymentIds[] = $payment->id;
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
                    'payment_ids' => $paymentIds,  // Payment IDs for all expenses in this settlement
                ];
            }
        }

        return $settlements;
    }

    /**
     * Calculate settlement matrix for all members in a group.
     * Returns array with structure: [$fromUserId][$toUserId] = amount owed
     */
    private function calculateGroupSettlementMatrix(Group $group)
    {
        // Use personal settlement calculation for each member and build matrix
        $matrix = [];

        // Initialize matrix
        foreach ($group->members as $member) {
            $matrix[$member->id] = [];
            foreach ($group->members as $other) {
                if ($member->id !== $other->id) {
                    $matrix[$member->id][$other->id] = 0;
                }
            }
        }

        // For each member, calculate their personal settlement
        foreach ($group->members as $member) {
            $settlement = $this->calculateSettlement($group, $member);

            // Convert settlement to matrix
            foreach ($settlement as $item) {
                $personId = $item['user']->id;
                $amount = $item['net_amount']; // Positive = member owes, Negative = person owes member

                if ($amount > 0) {
                    // Member owes personId
                    $matrix[$member->id][$personId] = $amount;
                } else if ($amount < 0) {
                    // PersonId owes member
                    $matrix[$personId][$member->id] = abs($amount);
                }
            }
        }

        // Convert to readable format with user data
        // Only include positive amounts (actual debts)
        $result = [];
        foreach ($group->members as $fromUser) {
            $result[$fromUser->id] = [
                'user' => $fromUser,
                'owes' => []
            ];

            foreach ($matrix[$fromUser->id] as $toUserId => $amount) {
                if ($amount > 0) {
                    $toUser = $group->members->find($toUserId);
                    $result[$fromUser->id]['owes'][$toUserId] = [
                        'user' => $toUser,
                        'amount' => round($amount, 2),
                    ];
                }
            }
        }

        return $result;
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

            // Notify the person who paid
            $this->notificationService->createNotification($payment->split->user, [
                'type' => 'payment_rejected',
                'title' => 'Payment Rejected',
                'message' => "{$user->name} rejected your payment for {$expense->title}",
                'data' => ['payment_id' => $payment->id, 'expense_id' => $expense->id, 'reason' => $validated['reason']],
            ]);

            return back()->with('success', 'Payment rejected. User has been notified.');
        } catch (\Exception $e) {
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
}
