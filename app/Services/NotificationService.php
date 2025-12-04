<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Group;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Notify group members about a new expense.
     *
     * @param Expense $expense
     * @param User $creator
     */
    public function notifyExpenseCreated(Expense $expense, User $creator): void
    {
        $group = $expense->group;
        $members = $group->members()->where('users.id', '!=', $creator->id)->get();

        foreach ($members as $member) {
            $this->createNotification($member, [
                'type' => 'expense_created',
                'title' => 'New Expense in ' . $group->name,
                'message' => "{$creator->name} added an expense: {$expense->title}",
                'data' => [
                    'expense_id' => $expense->id,
                    'group_id' => $group->id,
                ],
            ]);
        }
    }

    /**
     * Notify about a payment being marked.
     *
     * @param Payment $payment
     * @param User $paidBy
     */
    public function notifyPaymentMarked(Payment $payment, User $paidBy): void
    {
        $expense = $payment->split->expense;
        $group = $expense->group;
        $payer = $expense->payer;

        // Notify the payer
        $this->createNotification($payer, [
            'type' => 'payment_marked',
            'title' => 'Payment Received',
            'message' => "{$paidBy->name} marked their payment as paid for {$expense->title}",
            'data' => [
                'expense_id' => $expense->id,
                'group_id' => $group->id,
                'payment_id' => $payment->id,
            ],
        ]);
    }

    /**
     * Notify about a new comment on an expense.
     *
     * @param Expense $expense
     * @param User $commenter
     * @param string $message
     */
    public function notifyCommentAdded(Expense $expense, User $commenter, string $message): void
    {
        $group = $expense->group;
        $members = $group->members()->where('users.id', '!=', $commenter->id)->get();

        foreach ($members as $member) {
            $this->createNotification($member, [
                'type' => 'comment_added',
                'title' => 'New Comment in ' . $group->name,
                'message' => "{$commenter->name} commented on {$expense->title}",
                'data' => [
                    'expense_id' => $expense->id,
                    'group_id' => $group->id,
                ],
            ]);
        }
    }

    /**
     * Notify about user being added to a group.
     *
     * @param User $user
     * @param Group $group
     * @param User $addedBy
     */
    public function notifyUserAddedToGroup(User $user, Group $group, User $addedBy): void
    {
        $this->createNotification($user, [
            'type' => 'added_to_group',
            'title' => 'Added to Group',
            'message' => "{$addedBy->name} added you to {$group->name}",
            'data' => [
                'group_id' => $group->id,
            ],
        ]);
    }

    /**
     * Create a notification record in database.
     *
     * @param User $user
     * @param array $data
     */
    private function createNotification(User $user, array $data): void
    {
        // Store in database (you may need to create a notifications table)
        DB::table('notifications')->insert([
            'user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'data' => json_encode($data['data']),
            'read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // You can also send email/SMS here
        // Mail::send(new ExpenseNotification($user, $data));
    }

    /**
     * Send payment reminder notification.
     *
     * @param User $user
     * @param Expense $expense
     */
    public function sendPaymentReminder(User $user, Expense $expense): void
    {
        $this->createNotification($user, [
            'type' => 'payment_reminder',
            'title' => 'Payment Reminder',
            'message' => "You have an unpaid expense: {$expense->title}",
            'data' => [
                'expense_id' => $expense->id,
                'group_id' => $expense->group_id,
            ],
        ]);
    }
}
