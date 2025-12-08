<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class ActivityService
{
    /**
     * Log a group created activity
     */
    public static function logGroupCreated($group)
    {
        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'group_created',
            'title' => "Group '{$group->name}' created",
            'description' => "Created expense sharing group",
            'category' => 'group',
        ]);
    }

    /**
     * Log a user added to group activity
     */
    public static function logUserAdded($group, $addedUser)
    {
        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'user_added',
            'title' => "{$addedUser->name} added to {$group->name}",
            'description' => Auth::user()->name . " added {$addedUser->name} to the group",
            'category' => 'group',
            'related_users' => [$addedUser->id],
        ]);
    }

    /**
     * Log an expense created activity
     */
    public static function logExpenseCreated($group, $expense)
    {
        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'expense_created',
            'title' => $expense->title,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'category' => 'expense',
            'related_id' => $expense->id,
            'related_type' => 'Expense',
            'metadata' => json_encode([
                'payer_id' => $expense->payer_id,
                'payer_name' => $expense->payer->name,
                'split_type' => $expense->split_type,
                'split_count' => $expense->splits->count(),
                'date' => $expense->date->toDateString(),
            ]),
        ]);
    }

    /**
     * Log an advance paid activity
     */
    public static function logAdvancePaid($group, $advance, $members = [])
    {
        $memberNames = [];
        foreach ($members as $memberId) {
            $memberNames[] = \App\Models\User::find($memberId)?->name;
        }

        $memberList = implode(', ', array_filter($memberNames));

        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'advance_paid',
            'title' => "Advance to {$advance->sentTo->name}",
            'description' => $advance->description,
            'amount' => $advance->amount_per_person,
            'category' => 'advance',
            'related_users' => $members,
            'related_id' => $advance->id,
            'related_type' => 'Advance',
            'metadata' => json_encode([
                'sent_to_id' => $advance->sent_to_user_id,
                'sent_to_name' => $advance->sentTo->name,
                'senders' => $memberList,
                'amount_per_person' => $advance->amount_per_person,
                'date' => $advance->date->toDateString(),
            ]),
        ]);
    }

    /**
     * Log a payment made activity
     */
    public static function logPaymentMade($group, $payment, $fromUser, $toUser)
    {
        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'payment_made',
            'title' => "{$fromUser->name} paid {$toUser->name} - {$payment->amount}",
            'description' => "{$fromUser->name} paid {$payment->amount} to {$toUser->name}",
            'amount' => $payment->amount,
            'category' => 'payment',
            'related_users' => [$fromUser->id, $toUser->id],
            'related_id' => $payment->id,
            'related_type' => 'Payment',
        ]);
    }

    /**
     * Log a settlement confirmed activity
     */
    public static function logSettlementConfirmed($group, $settlement, $fromUser, $toUser)
    {
        return Activity::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'type' => 'settlement_confirmed',
            'title' => "{$fromUser->name} confirmed payment of {$settlement->amount} to {$toUser->name}",
            'description' => "Settlement confirmed: {$fromUser->name} paid {$toUser->name} {$settlement->amount}",
            'amount' => $settlement->amount,
            'category' => 'settlement',
            'related_users' => [$fromUser->id, $toUser->id],
            'related_id' => $settlement->id,
            'related_type' => 'SettlementConfirmation',
        ]);
    }

    /**
     * Get recent activities for a group
     */
    public static function getGroupActivities($groupId, $limit = 20)
    {
        return Activity::where('group_id', $groupId)
            ->with(['user', 'group'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unread activities for a user
     */
    public static function getUnreadActivities($userId, $groupId = null)
    {
        $query = Activity::where('user_id', '!=', $userId);

        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        return $query->with(['user', 'group'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    /**
     * Get timeline for PDF export
     */
    public static function getTimelineForPdf($groupId)
    {
        return Activity::where('group_id', $groupId)
            ->with(['user', 'group'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
