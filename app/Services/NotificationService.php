<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Group;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private FirebaseService $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

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
                    'expense_id' => (string)$expense->id,
                    'group_id' => (string)$group->id,
                    'type' => 'expense_created',
                ],
            ]);

            // Send push notification to device
            $this->sendPushNotification(
                $member,
                'New Expense in ' . $group->name,
                "{$creator->name} added an expense: {$expense->title}",
                [
                    'expense_id' => (string)$expense->id,
                    'group_id' => (string)$group->id,
                    'type' => 'expense_created',
                ]
            );
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
                'expense_id' => (string)$expense->id,
                'group_id' => (string)$group->id,
                'payment_id' => (string)$payment->id,
                'type' => 'payment_marked',
            ],
        ]);

        // Send push notification
        $this->sendPushNotification(
            $payer,
            'Payment Received',
            "{$paidBy->name} marked their payment as paid for {$expense->title}",
            [
                'expense_id' => (string)$expense->id,
                'group_id' => (string)$group->id,
                'payment_id' => (string)$payment->id,
                'type' => 'payment_marked',
            ]
        );
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
                    'expense_id' => (string)$expense->id,
                    'group_id' => (string)$group->id,
                    'type' => 'comment_added',
                ],
            ]);

            // Send push notification
            $this->sendPushNotification(
                $member,
                'New Comment in ' . $group->name,
                "{$commenter->name} commented on {$expense->title}",
                [
                    'expense_id' => (string)$expense->id,
                    'group_id' => (string)$group->id,
                    'type' => 'comment_added',
                ]
            );
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
                'group_id' => (string)$group->id,
                'type' => 'added_to_group',
            ],
        ]);

        // Send push notification
        $this->sendPushNotification(
            $user,
            'Added to Group',
            "{$addedBy->name} added you to {$group->name}",
            [
                'group_id' => (string)$group->id,
                'type' => 'added_to_group',
            ]
        );
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
                'expense_id' => (string)$expense->id,
                'group_id' => (string)$expense->group_id,
                'type' => 'payment_reminder',
            ],
        ]);

        // Send push notification
        $this->sendPushNotification(
            $user,
            'Payment Reminder',
            "You have an unpaid expense: {$expense->title}",
            [
                'expense_id' => (string)$expense->id,
                'group_id' => (string)$expense->group_id,
                'type' => 'payment_reminder',
            ]
        );
    }

    /**
     * Create a notification record in database and send push notification.
     *
     * @param User $user
     * @param array $data
     */
    private function createNotification(User $user, array $data): void
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Failed to create notification in database', [
                'user_id' => $user->id,
                'type' => $data['type'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send push notification via Firebase to all user's devices.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     */
    private function sendPushNotification(User $user, string $title, string $body, array $data): void
    {
        try {
            // Get all active device tokens for the user
            $deviceTokens = $user->deviceTokens()
                ->where('active', true)
                ->pluck('token')
                ->toArray();

            if (empty($deviceTokens)) {
                Log::info('No active device tokens for user', ['user_id' => $user->id]);
                return;
            }

            // Send to each device
            $result = $this->firebaseService->sendBulkNotification(
                $deviceTokens,
                $title,
                $body,
                $data
            );

            Log::info('Push notification sent', [
                'user_id' => $user->id,
                'successful' => $result['successful'],
                'failed' => $result['failed'],
                'total' => $result['total'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
