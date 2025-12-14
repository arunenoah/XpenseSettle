<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExpenseSplit;
use App\Models\Expense;

echo "=== FIXING ZERO-AMOUNT SPLITS ===\n\n";

// Find all splits with zero amounts
$zeroSplits = ExpenseSplit::where('share_amount', 0)
    ->with('expense.splits')
    ->get();

echo "Found {$zeroSplits->count()} splits with zero amounts\n\n";

$fixed = 0;
$skipped = 0;

foreach ($zeroSplits as $split) {
    $expense = $split->expense;
    
    // Skip if expense is itemwise (those should be zero)
    if ($expense->split_type === 'itemwise') {
        $skipped++;
        continue;
    }
    
    // Get all splits for this expense
    $allSplits = $expense->splits;
    $totalMembers = $allSplits->count();
    
    if ($totalMembers == 0) {
        echo "⚠️  Expense '{$expense->title}' has no splits, skipping\n";
        $skipped++;
        continue;
    }
    
    // Calculate what the share should be based on split type
    if ($expense->split_type === 'equal') {
        // Equal split
        $shareAmount = $expense->amount / $totalMembers;
    } else {
        // Custom split - check if other splits have amounts
        $nonZeroSplits = $allSplits->where('share_amount', '>', 0);
        $totalNonZero = $nonZeroSplits->sum('share_amount');
        $zeroCount = $allSplits->where('share_amount', 0)->count();
        
        if ($zeroCount == $totalMembers) {
            // All splits are zero, distribute equally
            $shareAmount = $expense->amount / $totalMembers;
        } else {
            // Some splits have amounts, calculate remaining
            $remaining = $expense->amount - $totalNonZero;
            $shareAmount = $zeroCount > 0 ? $remaining / $zeroCount : 0;
        }
    }
    
    // Update the split
    $split->share_amount = round($shareAmount, 2);
    $split->save();
    
    echo "✓ Fixed Split ID {$split->id}: Expense '{$expense->title}' → \${$shareAmount}\n";
    $fixed++;
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Fixed: {$fixed} splits\n";
echo "  Skipped: {$skipped} splits\n";
echo "========================================\n\n";

echo "✅ Done! All zero-amount splits have been fixed.\n";
