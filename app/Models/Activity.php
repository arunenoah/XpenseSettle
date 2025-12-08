<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'type',
        'title',
        'description',
        'amount',
        'category',
        'related_users',
        'related_id',
        'related_type',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'related_users' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the group that owns the activity.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who created the activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unread activities for a user in a group
     */
    public function scopeUnread($query, $userId, $groupId = null)
    {
        $query->where('user_id', '!=', $userId);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        return $query;
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->whereDate('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get emoji for activity type
     */
    public function getIconAttribute()
    {
        return match ($this->type) {
            'group_created' => '👥',
            'user_added' => '➕',
            'expense_created' => '📝',
            'advance_paid' => '💰',
            'payment_made' => '✅',
            'settlement_confirmed' => '🎯',
            default => '📌',
        };
    }
}
