<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advance extends Model
{
    protected $fillable = ['group_id', 'sent_to_user_id', 'amount_per_person', 'date', 'description'];

    protected $casts = [
        'date' => 'date',
        'amount_per_person' => 'decimal:2',
    ];

    /**
     * Get the group this advance belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who received the advance.
     */
    public function sentTo()
    {
        return $this->belongsTo(User::class, 'sent_to_user_id');
    }

    /**
     * Get the users who sent this advance.
     */
    public function senders()
    {
        return $this->belongsToMany(User::class, 'advance_senders', 'advance_id', 'user_id');
    }

    /**
     * Get total amount sent in this advance.
     */
    public function getTotalAmount()
    {
        return $this->amount_per_person * $this->senders()->count();
    }
}
