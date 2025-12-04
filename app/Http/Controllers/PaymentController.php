<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
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
            'proof_of_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        try {
            // Create or update payment
            $payment = $this->paymentService->markAsPaid($split, $user, $validated);

            // Handle proof of payment attachment
            if ($request->hasFile('proof_of_payment')) {
                $this->attachmentService->uploadAttachment(
                    $request->file('proof_of_payment'),
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
