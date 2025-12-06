<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseItem extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expense_id',
        'user_id',
        'name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the expense that owns this item.
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * Get the user this item is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
