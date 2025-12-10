<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    protected $fillable = ['group_id', 'name', 'email', 'phone', 'family_count'];

    /**
     * Get the group this contact belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the group member record.
     */
    public function groupMember()
    {
        return $this->hasOne(GroupMember::class, 'contact_id');
    }

    /**
     * Get expense splits for this contact.
     */
    public function expenseSplits()
    {
        return $this->hasMany(ExpenseSplit::class, 'contact_id');
    }
}
