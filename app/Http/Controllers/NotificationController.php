<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Expense;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->get('filter', 'unread'); // 'unread' or 'all'

        $query = Activity::where('user_id', '!=', $user->id)
            ->whereIn('group_id', $user->groups()->pluck('groups.id'))
            ->with(['group', 'user'])
            ->orderByDesc('created_at');

        if ($filter === 'unread') {
            $query->unreadFor($user->id);
        }

        $activities = $query->limit(50)->get()->map(function ($activity) use ($user) {
            $data = [
                'id' => $activity->id,
                'type' => $activity->type,
                'title' => $activity->title,
                'description' => $activity->description,
                'amount' => $activity->amount,
                'icon' => $activity->icon,
                'created_at' => $activity->created_at,
                'group_name' => $activity->group?->name,
                'user_name' => $activity->user?->name,
                'metadata' => $activity->metadata,
                'is_read' => $activity->isReadBy($user->id),
                'user_share' => null, // Default to null
            ];

            // For expenses, calculate user's share amount
            if ($activity->type === 'expense_created' && $activity->related_id) {
                $expense = Expense::find($activity->related_id);
                if ($expense) {
                    $userSplit = $expense->splits()->where('user_id', $user->id)->first();
                    if ($userSplit) {
                        $data['user_share'] = $userSplit->share_amount;
                    }
                }
            }

            return $data;
        });

        $unreadCount = Activity::where('user_id', '!=', $user->id)
            ->whereIn('group_id', $user->groups()->pluck('groups.id'))
            ->unreadFor($user->id)
            ->count();

        return response()->json([
            'activities' => $activities,
            'unread_count' => $unreadCount,
            'filter' => $filter,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $activity = Activity::findOrFail($id);
        $activity->markAsReadBy(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        
        $activities = Activity::where('user_id', '!=', $user->id)
            ->whereIn('group_id', $user->groups()->pluck('groups.id'))
            ->unreadFor($user->id)
            ->get();

        foreach ($activities as $activity) {
            $activity->markAsReadBy($user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'count' => $activities->count(),
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $user = auth()->user();
        
        $count = Activity::where('user_id', '!=', $user->id)
            ->whereIn('group_id', $user->groups()->pluck('groups.id'))
            ->unreadFor($user->id)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }
}
