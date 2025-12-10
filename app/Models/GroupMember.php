<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    /** @use HasFactory<\Database\Factories\GroupMemberFactory> */
    use HasFactory;

    protected $fillable = ['group_id', 'user_id', 'contact_id', 'role', 'family_count'];

    protected $casts = [
        'user_id' => 'integer',
        'contact_id' => 'integer',
    ];

    /**
     * Get the group.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    /**
     * Get the contact.
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the member (User or Contact).
     */
    public function getMember()
    {
        return $this->user ?? $this->contact;
    }

    /**
     * Get member name.
     */
    public function getMemberName(): string
    {
        return $this->user?->name ?? $this->contact?->name ?? 'Unknown';
    }

    /**
     * Check if this is an active user member.
     */
    public function isActiveUser(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if this is a contact member.
     */
    public function isContact(): bool
    {
        return $this->contact_id !== null;
    }
}
