<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedPayment extends Model
{
    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'received_date',
        'description',
        'status',
    ];

    protected $casts = [
        'received_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the group this payment belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who paid.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who received the payment.
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
