<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Group;
use Illuminate\Console\Command;

class AuditAndFixExpenseSplits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:audit-and-fix-splits {--fix} {--group=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all expenses and fix those with incorrect family-count splits. Use --fix to apply changes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== EXPENSE SPLITS AUDIT ===');
        $this->newLine();

        $shouldFix = $this->option('fix');
        $groupId = $this->option('group');

        if (!$shouldFix) {
            $this->warn('DRY RUN MODE - Use --fix flag to actually fix expenses');
            $this->newLine();
        }

        // Get all groups or specific group
        $groups = $groupId 
            ? Group::where('id', $groupId)->get()
            : Group::all();

        $totalProblematic = 0;
        $fixedExpenses = [];

        foreach ($groups as $group) {
            $this->info("Auditing Group: {$group->name} (ID: {$group->id})");
            
            $totalHeadcount = $group->members()->sum('family_count');
            $this->line("  Total family members: {$totalHeadcount}");
            
            $expenses = Expense::where('group_id', $group->id)
                ->with('splits.user', 'payer')
                ->get();

            $problematicInGroup = 0;

            foreach ($expenses as $expense) {
                // Skip itemwise - they don't affect settlement
                if ($expense->split_type === 'itemwise') {
                    continue;
                }

                $hasIssue = false;
                $perPerson = $expense->amount / $totalHeadcount;

                // Check each split
                foreach ($expense->splits as $split) {
                    $familyCount = $group->members()
                        ->where('user_id', $split->user_id)
                        ->first()
                        ?->pivot
                        ?->family_count ?? 1;

                    $expectedAmount = round($perPerson * $familyCount, 2);
                    $actualAmount = $split->share_amount;

                    if (abs($actualAmount - $expectedAmount) > 0.01) {
                        $hasIssue = true;
                        break;
                    }
                }

                if ($hasIssue) {
                    $problematicInGroup++;
                    $totalProblematic++;

                    $this->warn("  ⚠️  Expense {$expense->id}: {$expense->title} (\$" . number_format($expense->amount, 2) . ")");

                    if ($shouldFix) {
                        $newExpense = $this->fixExpense($group, $expense);
                        $fixedExpenses[] = [
                            'old_id' => $expense->id,
                            'new_id' => $newExpense->id,
                            'title' => $expense->title,
                            'amount' => $expense->amount,
                        ];
                        $this->line("    ✓ Fixed: {$expense->id} → {$newExpense->id}");
                    }
                }
            }

            if ($problematicInGroup === 0) {
                $this->line("  ✓ All expenses have correct splits!");
            } else {
                $this->line("  Found {$problematicInGroup} problematic expense(s)");
            }
            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->line("Total problematic expenses found: {$totalProblematic}");

        if ($shouldFix && count($fixedExpenses) > 0) {
            $this->line("Expenses fixed: " . count($fixedExpenses));
            $this->newLine();
            $this->info("Fixed Expenses:");
            $this->table(
                ['Old ID', 'New ID', 'Title', 'Amount'],
                array_map(fn($exp) => [
                    $exp['old_id'],
                    $exp['new_id'],
                    $exp['title'],
                    '$' . number_format($exp['amount'], 2),
                ], $fixedExpenses)
            );
        } elseif (!$shouldFix && $totalProblematic > 0) {
            $this->newLine();
            $this->line('Run with --fix flag to apply fixes:');
            $this->line('  php artisan expenses:audit-and-fix-splits --fix');
        }
    }

    /**
     * Fix a single expense by recreating it with correct splits.
     */
    private function fixExpense(Group $group, Expense $expense)
    {
        // Store original details
        $expenseData = [
            'group_id' => $expense->group_id,
            'payer_id' => $expense->payer_id,
            'title' => $expense->title,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'date' => $expense->date,
            'split_type' => 'equal',
        ];

        // Delete old expense and splits
        $oldId = $expense->id;
        $expense->delete();

        // Create new expense
        $newExpense = Expense::create($expenseData);

        // Calculate and create correct splits
        $members = $group->members()->get();
        $totalHeadcount = $members->sum(fn($m) => $m->pivot->family_count);
        $perPersonAmount = $newExpense->amount / $totalHeadcount;

        $memberSplits = [];
        $splitTotal = 0;

        foreach ($members as $member) {
            $familyCount = $member->pivot->family_count;
            $splitAmount = round($perPersonAmount * $familyCount, 2);
            $memberSplits[$member->id] = $splitAmount;
            $splitTotal += $splitAmount;
        }

        // Adjust for rounding
        $difference = round(($newExpense->amount - $splitTotal) * 100) / 100;
        if (abs($difference) > 0.001) {
            $lastMemberId = array_key_last($memberSplits);
            $memberSplits[$lastMemberId] += $difference;
        }

        // Create split records
        foreach ($memberSplits as $userId => $splitAmount) {
            ExpenseSplit::create([
                'expense_id' => $newExpense->id,
                'user_id' => $userId,
                'share_amount' => $splitAmount,
            ]);
        }

        return $newExpense;
    }
}
