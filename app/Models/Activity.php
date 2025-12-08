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
        'read_by',
        'read_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'related_users' => 'array',
        'metadata' => 'array',
        'read_by' => 'array',
        'read_at' => 'datetime',
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

    /**
     * Check if activity is read by a specific user
     */
    public function isReadBy($userId)
    {
        $readBy = $this->read_by ?? [];
        return in_array($userId, $readBy);
    }

    /**
     * Mark activity as read by a specific user
     */
    public function markAsReadBy($userId)
    {
        $readBy = $this->read_by ?? [];
        if (!in_array($userId, $readBy)) {
            $readBy[] = $userId;
            $this->read_by = $readBy;
            $this->read_at = now();
            $this->save();
        }
    }

    /**
     * Scope to get unread activities for a specific user
     */
    public function scopeUnreadFor($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', '!=', $userId)
              ->where(function ($q2) use ($userId) {
                  $q2->whereNull('read_by')
                     ->orWhereJsonDoesntContain('read_by', $userId);
              });
        });
    }
}
