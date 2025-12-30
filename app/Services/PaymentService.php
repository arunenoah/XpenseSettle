<?php

namespace App\Services;

use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\ReceivedPayment;
use App\Models\User;

class PaymentService
{
    /**
     * Mark a payment as paid.
     *
     * This creates a Payment record AND automatically creates a ReceivedPayment
     * record to actually settle the balance between the payer and the person who owes.
     *
     * @param ExpenseSplit $split
     * @param User $paidBy (the person who owes, marking they've paid)
     * @param array $data
     * @return Payment
     */
    public function markAsPaid(ExpenseSplit $split, User $paidBy, array $data = []): Payment
    {
        $payment = $split->payment ?? new Payment();
        $isNewPayment = !$payment->id; // Track if this is a new payment

        $payment->expense_split_id = $split->id;
        $payment->paid_by = $paidBy->id;
        $payment->status = 'paid';
        $payment->paid_date = $data['paid_date'] ?? now()->toDateString();
        $payment->notes = $data['notes'] ?? null;
        $payment->save();

        // Only create ReceivedPayment on first marking as paid
        // This prevents duplicate records if markAsPaid is called multiple times
        if ($isNewPayment) {
            $expense = $split->expense;
            $payer = $expense->payer;

            // Create ReceivedPayment: paidBy is sending money to payer
            // This automatically reduces the settlement balance
            ReceivedPayment::create([
                'group_id' => $expense->group_id,
                'from_user_id' => $paidBy->id,          // Person who owes (sending payment)
                'to_user_id' => $payer->id,              // Payer (receiving payment)
                'amount' => $split->share_amount,
                'received_date' => $data['paid_date'] ?? now()->toDateString(),
                'description' => $data['notes'] ?? "Payment for: {$expense->title}",
                'status' => 'completed',
            ]);
        }

        return $payment;
    }

    /**
     * Mark a payment as rejected.
     *
     * @param Payment $payment
     * @param string $reason
     * @return Payment
     */
    public function rejectPayment(Payment $payment, string $reason = ''): Payment
    {
        $payment->update([
            'status' => 'rejected',
            'notes' => $reason,
        ]);

        return $payment;
    }

    /**
     * Create a payment record for a split.
     *
     * @param ExpenseSplit $split
     * @return Payment
     */
    public function createPaymentRecord(ExpenseSplit $split): Payment
    {
        return Payment::create([
            'expense_split_id' => $split->id,
            'paid_by' => $split->user_id,
            'status' => 'pending',
        ]);
    }

    /**
     * Get pending payments for a user.
     *
     * @param User $user
     * @param int|null $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingPaymentsForUser(User $user, ?int $groupId = null)
    {
        $query = Payment::whereHas('split', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('status', 'pending');

        if ($groupId) {
            $query->whereHas('split.expense', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        return $query->with(['split.expense', 'split.user'])->get();
    }

    /**
     * Get payment statistics for a user.
     *
     * @param User $user
     * @param int|null $groupId
     * @return array
     */
    public function getPaymentStats(User $user, ?int $groupId = null): array
    {
        $query = Payment::join('expense_splits', 'payments.expense_split_id', '=', 'expense_splits.id')
            ->where('expense_splits.user_id', $user->id);

        if ($groupId) {
            $query->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
                ->where('expenses.group_id', $groupId);
        }

        $total = (clone $query)->sum('expense_splits.share_amount');
        $paid = (clone $query)->where('payments.status', 'paid')->sum('expense_splits.share_amount');
        $pending = (clone $query)->where('payments.status', 'pending')->sum('expense_splits.share_amount');

        return [
            'total_amount' => $total ?? 0,
            'paid_amount' => $paid ?? 0,
            'pending_amount' => $pending ?? 0,
        ];
    }
}
