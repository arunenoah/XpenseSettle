<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['created_by', 'name', 'icon', 'description', 'currency', 'plan', 'plan_expires_at', 'ocr_scans_used'];

    protected $casts = [
        'plan_expires_at' => 'datetime',
    ];

    /**
     * Get the user who created the group.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get members of the group.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Get group members records.
     */
    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get expenses in this group.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Check if user is admin of this group.
     */
    public function isAdmin(User $user)
    {
        return $this->groupMembers()
                    ->where('user_id', $user->id)
                    ->where('role', 'admin')
                    ->exists();
    }

    /**
     * Check if user is member of this group.
     */
    public function hasMember(User $user)
    {
        return $this->members()->where('group_members.user_id', $user->id)->exists();
    }
}
