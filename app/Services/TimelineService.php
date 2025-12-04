<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TimelineService
{
    /**
     * Get group activity timeline.
     */
    public function getGroupTimeline(Group $group, ?User $user = null, array $filters = []): array
    {
        $activities = [];

        // Get expense activities
        $expenses = $group->expenses()
            ->with('payer', 'splits.user')
            ->when(isset($filters['date_from']), function ($q) use ($filters) {
                $q->where('date', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($q) use ($filters) {
                $q->where('date', '<=', $filters['date_to']);
            })
            ->when(isset($filters['user_id']), function ($q) use ($filters) {
                $q->where('payer_id', $filters['user_id']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            $activities[] = [
                'type' => 'expense_created',
                'timestamp' => $expense->created_at,
                'user' => $expense->payer,
                'data' => [
                    'expense' => $expense,
                    'title' => $expense->title,
                    'amount' => $expense->amount,
                    'participants' => $expense->splits->pluck('user')->unique('id'),
                ],
                'icon' => '💰',
                'color' => 'blue',
            ];
        }

        // Get payment activities
        $payments = DB::table('payments')
            ->join('expense_splits', 'payments.expense_split_id', '=', 'expense_splits.id')
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->join('users', 'payments.paid_by', '=', 'users.id')
            ->where('expenses.group_id', $group->id)
            ->where('payments.status', 'paid')
            ->when(isset($filters['date_from']), function ($q) use ($filters) {
                $q->where('payments.created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($q) use ($filters) {
                $q->where('payments.created_at', '<=', $filters['date_to']);
            })
            ->when(isset($filters['user_id']), function ($q) use ($filters) {
                $q->where('payments.paid_by', $filters['user_id']);
            })
            ->select(
                'payments.*',
                'expenses.title as expense_title',
                'expenses.amount as expense_amount',
                'users.name as payer_name',
                'users.id as payer_id'
            )
            ->orderBy('payments.created_at', 'desc')
            ->get();

        foreach ($payments as $payment) {
            $activities[] = [
                'type' => 'payment_made',
                'timestamp' => $payment->created_at,
                'user' => User::find($payment->payer_id),
                'data' => [
                    'payment' => $payment,
                    'expense_title' => $payment->expense_title,
                    'amount' => $payment->expense_amount,
                ],
                'icon' => '✅',
                'color' => 'green',
            ];
        }

        // Get member activities
        $memberActivities = DB::table('group_members')
            ->join('users', 'group_members.user_id', '=', 'users.id')
            ->where('group_members.group_id', $group->id)
            ->when(isset($filters['date_from']), function ($q) use ($filters) {
                $q->where('group_members.created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($q) use ($filters) {
                $q->where('group_members.created_at', '<=', $filters['date_to']);
            })
            ->select('group_members.*', 'users.name as user_name', 'users.id as user_id')
            ->orderBy('group_members.created_at', 'desc')
            ->get();

        foreach ($memberActivities as $memberActivity) {
            $activities[] = [
                'type' => 'member_joined',
                'timestamp' => $memberActivity->created_at,
                'user' => User::find($memberActivity->user_id),
                'data' => [
                    'role' => $memberActivity->role,
                ],
                'icon' => '👤',
                'color' => 'purple',
            ];
        }

        // Get comment activities
        $comments = DB::table('comments')
            ->join('expenses', 'comments.expense_id', '=', 'expenses.id')
            ->join('users', 'comments.user_id', '=', 'users.id')
            ->where('expenses.group_id', $group->id)
            ->when(isset($filters['date_from']), function ($q) use ($filters) {
                $q->where('comments.created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function ($q) use ($filters) {
                $q->where('comments.created_at', '<=', $filters['date_to']);
            })
            ->when(isset($filters['user_id']), function ($q) use ($filters) {
                $q->where('comments.user_id', $filters['user_id']);
            })
            ->select(
                'comments.*',
                'expenses.title as expense_title',
                'users.name as commenter_name',
                'users.id as commenter_id'
            )
            ->orderBy('comments.created_at', 'desc')
            ->get();

        foreach ($comments as $comment) {
            $activities[] = [
                'type' => 'comment_added',
                'timestamp' => $comment->created_at,
                'user' => User::find($comment->commenter_id),
                'data' => [
                    'comment' => $comment,
                    'expense_title' => $comment->expense_title,
                    'content' => $comment->content,
                ],
                'icon' => '💬',
                'color' => 'gray',
            ];
        }

        // Sort all activities by timestamp
        usort($activities, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        // Apply anonymity if requested
        if (isset($filters['anonymous']) && $filters['anonymous']) {
            $activities = $this->anonymizeActivities($activities, $user);
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $perPage;

        return [
            'activities' => array_slice($activities, $offset, $perPage),
            'total' => count($activities),
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil(count($activities) / $perPage),
        ];
    }

    /**
     * Anonymize activities for privacy.
     */
    private function anonymizeActivities(array $activities, ?User $currentUser): array
    {
        return array_map(function ($activity) use ($currentUser) {
            // Don't anonymize current user's activities
            if ($currentUser && $activity['user']->id === $currentUser->id) {
                return $activity;
            }

            // Anonymize user info
            $activity['user'] = (object) [
                'id' => $activity['user']->id,
                'name' => 'Member ' . substr(md5($activity['user']->id), 0, 6),
                'is_anonymous' => true,
            ];

            return $activity;
        }, $activities);
    }

    /**
     * Get expense history (all changes).
     */
    public function getExpenseHistory(int $expenseId): array
    {
        // This would require an audit log table
        // For now, return basic info
        $expense = \App\Models\Expense::with('payer', 'splits.user', 'splits.payment')->findOrFail($expenseId);

        $history = [];

        // Creation event
        $history[] = [
            'type' => 'created',
            'timestamp' => $expense->created_at,
            'user' => $expense->payer,
            'data' => [
                'amount' => $expense->amount,
                'split_type' => $expense->split_type,
            ],
        ];

        // Payment events
        foreach ($expense->splits as $split) {
            if ($split->payment && $split->payment->status === 'paid') {
                $history[] = [
                    'type' => 'payment_made',
                    'timestamp' => $split->payment->created_at,
                    'user' => $split->user,
                    'data' => [
                        'amount' => $split->share_amount,
                        'status' => $split->payment->status,
                    ],
                ];
            }
        }

        // Status changes
        if ($expense->status === 'fully_paid') {
            $history[] = [
                'type' => 'fully_paid',
                'timestamp' => $expense->updated_at,
                'data' => [
                    'status' => 'fully_paid',
                ],
            ];
        }

        // Sort by timestamp
        usort($history, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        return $history;
    }

    /**
     * Get user activity summary in group.
     */
    public function getUserActivitySummary(Group $group, User $user): array
    {
        return [
            'expenses_created' => $group->expenses()->where('payer_id', $user->id)->count(),
            'payments_made' => DB::table('payments')
                ->join('expense_splits', 'payments.expense_split_id', '=', 'expense_splits.id')
                ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
                ->where('expenses.group_id', $group->id)
                ->where('payments.paid_by', $user->id)
                ->where('payments.status', 'paid')
                ->count(),
            'comments_posted' => DB::table('comments')
                ->join('expenses', 'comments.expense_id', '=', 'expenses.id')
                ->where('expenses.group_id', $group->id)
                ->where('comments.user_id', $user->id)
                ->count(),
            'total_amount_paid' => $group->expenses()->where('payer_id', $user->id)->sum('amount'),
            'member_since' => $group->groupMembers()->where('user_id', $user->id)->first()->created_at ?? null,
        ];
    }
}
