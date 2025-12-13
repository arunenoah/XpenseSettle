<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Expense;

echo "\n========== CHECKING FIXED EXPENSES ==========\n\n";

$fixedExpenses = [421, 422, 423];

foreach ($fixedExpenses as $expId) {
    $exp = Expense::find($expId);
    if (!$exp) continue;

    echo "Expense {$expId}: {$exp->description}\n";
    echo "  Payer: {$exp->payer_id}, Amount: \${$exp->amount}\n";
    echo "  Splits:\n";

    $totalSplits = 0;
    foreach ($exp->splits as $split) {
        echo "    User {$split->user_id}: \${$split->share_amount}\n";
        $totalSplits += $split->share_amount;
    }
    echo "    Total: \$$totalSplits\n\n";
}

echo "\n========== NOW RECALCULATE ARUN ↔ KARTHICK ==========\n\n";

$arun = 8;
$karthick = 11;

// Arun spent on Karthick
$arunSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $arun)
    ->with(['splits'])
    ->get();

foreach ($expenses as $expense) {
    $karthickSplit = $expense->splits->where('user_id', $karthick)->first();
    if ($karthickSplit && $expense->split_type !== 'itemwise') {
        $arunSpent += $karthickSplit->share_amount;
        echo "Arun paid: {$expense->description}: \${$karthickSplit->share_amount}\n";
    }
}

// Karthick spent on Arun
$karthickSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $karthick)
    ->with(['splits'])
    ->get();

foreach ($expenses as $expense) {
    $arunSplit = $expense->splits->where('user_id', $arun)->first();
    if ($arunSplit && $expense->split_type !== 'itemwise') {
        $karthickSpent += $arunSplit->share_amount;
        echo "Karthick paid: {$expense->description}: \${$arunSplit->share_amount}\n";
    }
}

echo "\nNet spent: (\$$arunSpent - \$$karthickSpent) = \$" . ($arunSpent - $karthickSpent) . "\n";
echo "Advance from Arun to Karthick: \$101\n\n";

$result = ($arunSpent - $karthickSpent) - 101;
echo "Final: \$" . ($arunSpent - $karthickSpent) . " - \$101 = \$" . $result . "\n";
echo "Expected: \$17.34\n";
echo "Match: " . (abs($result - 17.34) < 0.01 ? "✅ YES!" : "❌ NO") . "\n\n";
