<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /** @use HasFactory<\Database\Factories\ExpenseFactory> */
    use HasFactory;

    protected $fillable = ['group_id', 'payer_id', 'title', 'description', 'amount', 'split_type', 'date', 'status'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the group this expense belongs to.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the user who paid this expense.
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the expense splits.
     */
    public function splits()
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    /**
     * Get comments on this expense.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get attachments for this expense.
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
