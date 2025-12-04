<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = ['expense_split_id', 'paid_by', 'status', 'paid_date', 'notes'];

    protected $casts = [
        'paid_date' => 'date',
    ];

    /**
     * Get the expense split this payment is for.
     */
    public function split()
    {
        return $this->belongsTo(ExpenseSplit::class, 'expense_split_id');
    }

    /**
     * Get the user who paid.
     */
    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get attachments for this payment.
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
