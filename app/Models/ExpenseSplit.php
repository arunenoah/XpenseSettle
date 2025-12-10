<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseSplit extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseSplitFactory> */
    use HasFactory;

    protected $fillable = ['expense_id', 'user_id', 'contact_id', 'share_amount', 'percentage'];

    protected $casts = [
        'share_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'user_id' => 'integer',
        'contact_id' => 'integer',
    ];

    /**
     * Get the expense this split belongs to.
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the user this split is for.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    /**
     * Get the contact this split is for.
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
     * Get payments for this split.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
