<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Models\ExpenseSplit;
use App\Models\Group;
use App\Models\User;

class ExpenseService
{
    /**
     * Create a new expense with splits.
     *
     * @param Group $group
     * @param User $payer
     * @param array $data
     * @return Expense
     */
    public function createExpense(Group $group, User $payer, array $data): Expense
    {
        $expense = Expense::create([
            'group_id' => $group->id,
            'payer_id' => $payer->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'split_type' => $data['split_type'] ?? 'equal',
            'category' => $data['category'] ?? 'Other',
            'date' => $data['date'] ?? now()->toDateString(),
            'status' => 'pending',
        ]);

        // Create splits based on split_type
        if (isset($data['splits'])) {
            $this->createSplits($expense, $data['splits']);
        } elseif ($expense->split_type !== 'itemwise') {
            // Default: equal split among group members (but NOT for itemwise)
            $this->createEqualSplits($expense, $group);
        }

        return $expense;
    }

    /**
     * Create equal splits for an expense.
     *
     * @param Expense $expense
     * @param Group $group
     */
    private function createEqualSplits(Expense $expense, Group $group): void
    {
        // Load members with family_count from pivot
        $members = $group->members()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('family_count')
            ->get();

        if ($members->isEmpty()) {
            return;
        }

        // Calculate total headcount (considering family_count for each member)
        $totalHeadcount = 0;
        foreach ($members as $member) {
            // Get family_count from pivot data, default to 1 if not set or 0
            $familyCount = $member->pivot->family_count ?? 1;
            if ($familyCount <= 0) {
                $familyCount = 1; // Ensure minimum of 1 person per member
            }
            $totalHeadcount += $familyCount;
        }

        if ($totalHeadcount === 0) {
            return;
        }

        // Calculate per-headcount split amount (e.g., $100 / 10 people = $10 per person)
        $perHeadcountAmount = $expense->amount / $totalHeadcount;

        // Create splits: each member's share = per-headcount amount * their family count
        foreach ($members as $member) {
            $familyCount = $member->pivot->family_count ?? 1;
            if ($familyCount <= 0) {
                $familyCount = 1;
            }

            $shareAmount = $perHeadcountAmount * $familyCount;

            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $member->id,
                'share_amount' => round($shareAmount, 2),
            ]);
        }
    }

    /**
     * Create custom splits for an expense.
     *
     * @param Expense $expense
     * @param array $splits Array where key can be GroupMember ID or User/Contact ID, value is amount
     */
    private function createSplits(Expense $expense, array $splits): void
    {
        $group = $expense->group;

        foreach ($splits as $memberId => $data) {
            // Try to find as GroupMember ID first
            $groupMember = \App\Models\GroupMember::find($memberId);

            // If not found as GroupMember ID, try as User ID within this group
            if (!$groupMember) {
                $groupMember = \App\Models\GroupMember::where('group_id', $group->id)
                    ->where('user_id', $memberId)
                    ->first();
            }

            // If still not found, try as Contact ID within this group
            if (!$groupMember) {
                $groupMember = \App\Models\GroupMember::where('group_id', $group->id)
                    ->where('contact_id', $memberId)
                    ->first();
            }

            if (!$groupMember) {
                continue; // Skip if member not found
            }

            // Handle both simple amount and detailed split data
            $splitData = [
                'expense_id' => $expense->id,
                'share_amount' => is_array($data) ? ($data['amount'] ?? 0) : $data,
                'percentage' => is_array($data) ? ($data['percentage'] ?? null) : null,
            ];

            // Set either user_id or contact_id based on member type
            if ($groupMember->isActiveUser()) {
                $splitData['user_id'] = $groupMember->user_id;
                $splitData['contact_id'] = null;
            } else {
                $splitData['user_id'] = null;
                $splitData['contact_id'] = $groupMember->contact_id;
            }

            ExpenseSplit::create($splitData);
        }
    }

    /**
     * Create percentage-based splits for an expense.
     *
     * @param Expense $expense
     * @param array $percentages Array of user_id => percentage
     */
    public function createPercentageSplits(Expense $expense, array $percentages): void
    {
        $totalPercentage = array_sum($percentages);

        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception("Percentages must add up to 100%. Current total: {$totalPercentage}%");
        }

        foreach ($percentages as $userId => $percentage) {
            $shareAmount = round(($expense->amount * $percentage) / 100, 2);

            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $userId,
                'share_amount' => $shareAmount,
                'percentage' => $percentage,
            ]);
        }

        // Handle rounding issues - adjust the first split
        $totalSplit = $expense->splits()->sum('share_amount');
        if (abs($totalSplit - $expense->amount) > 0.01) {
            $diff = round($expense->amount - $totalSplit, 2);
            $firstSplit = $expense->splits()->first();
            $firstSplit->update(['share_amount' => $firstSplit->share_amount + $diff]);
        }
    }

    /**
     * Create custom adjustable splits with individual amounts.
     *
     * @param Expense $expense
     * @param array $customSplits Array of user_id => amount
     * @param bool $validateTotal Whether to validate that splits equal expense amount
     */
    public function createCustomAdjustableSplits(Expense $expense, array $customSplits, bool $validateTotal = true): void
    {
        $totalSplit = array_sum($customSplits);

        if ($validateTotal && abs($totalSplit - $expense->amount) > 0.01) {
            throw new \Exception(
                "Custom splits total (\${$totalSplit}) must equal expense amount (\${$expense->amount})"
            );
        }

        foreach ($customSplits as $userId => $amount) {
            ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $userId,
                'share_amount' => round($amount, 2),
            ]);
        }
    }

    /**
     * Update an expense.
     *
     * @param Expense $expense
     * @param array $data
     * @return Expense
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        // Only allow editing if not fully paid
        if ($expense->status === 'fully_paid') {
            throw new \Exception('Cannot edit a fully paid expense');
        }

        $expense->update([
            'title' => $data['title'] ?? $expense->title,
            'description' => $data['description'] ?? $expense->description,
            'amount' => $data['amount'] ?? $expense->amount,
            'split_type' => $data['split_type'] ?? $expense->split_type,
            'category' => $data['category'] ?? $expense->category,
            'date' => $data['date'] ?? $expense->date,
        ]);

        // Update splits if provided
        if (isset($data['splits'])) {
            $expense->splits()->delete();
            $this->createSplits($expense, $data['splits']);
        }

        return $expense;
    }

    /**
     * Delete an expense.
     *
     * @param Expense $expense
     * @return bool
     */
    public function deleteExpense(Expense $expense): bool
    {
        // Delete related records
        $expense->splits()->delete();
        $expense->comments()->delete();
        $expense->attachments()->delete();
        $expense->items()->delete();

        return $expense->delete();
    }

    /**
     * Create expense items from OCR extraction.
     *
     * @param Expense $expense
     * @param string $itemsJson JSON string of items
     */
    public function createExpenseItems(Expense $expense, string $itemsJson): void
    {
        try {
            $items = json_decode($itemsJson, true);

            if (empty($items) || !is_array($items)) {
                return;
            }

            foreach ($items as $item) {
                ExpenseItem::create([
                    'expense_id' => $expense->id,
                    'user_id' => $item['assigned_to'] ?? null,
                    'name' => $item['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => $item['total_price'] ?? 0,
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the expense creation
            \Log::warning('Failed to create expense items from OCR', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate settlement needed for an expense.
     *
     * @param Expense $expense
     * @return array
     */
    public function getExpenseSettlement(Expense $expense): array
    {
        $settlement = [];

        foreach ($expense->splits as $split) {
            // Only add settlement if the split is not the payer (and exists)
            if ($split->user_id && $split->user_id !== $expense->payer_id) {
                $settlement[] = [
                    'from' => $split->user,
                    'to' => $expense->payer,
                    'amount' => $split->share_amount,
                    'paid' => $split->payment && $split->payment->status === 'paid',
                ];
            } elseif ($split->contact_id) {
                // Handle contact splits - they always owe the payer
                $settlement[] = [
                    'from' => $split->contact,
                    'to' => $expense->payer,
                    'amount' => $split->share_amount,
                    'paid' => $split->payment && $split->payment->status === 'paid',
                ];
            }
        }

        return $settlement;
    }

    /**
     * Mark expense as fully paid.
     *
     * @param Expense $expense
     * @return Expense
     */
    public function markExpenseAsPaid(Expense $expense): Expense
    {
        // Load fresh splits with their payment relationships
        $expense->load('splits.payment');

        // Check if ALL splits have payments with status='paid'
        $allSplitsPaid = $expense->splits->every(function ($split) {
            return $split->payment && $split->payment->status === 'paid';
        });

        if ($allSplitsPaid && $expense->splits->count() > 0) {
            $expense->update(['status' => 'fully_paid']);
        }

        return $expense;
    }
}
