<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementConfirmation extends Model
{
    /** @use HasFactory<\Database\Factories\SettlementConfirmationFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount',
        'notes',
        'confirmed_at',
        'confirmed_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the group this settlement is for.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who paid.
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who received payment.
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Get the user who confirmed this settlement.
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get attachments for this settlement confirmation (photos, receipts, etc).
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
