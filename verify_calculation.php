<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Group;
use App\Models\Expense;
use App\Models\Advance;
use Illuminate\Support\Facades\DB;

$group = Group::find(14);
$arun = 8;      // Arun
$karthick = 11;  // Karthick (NOT 7!)

echo "\n========== CHECKING DATABASE FOR GROUP 14 ==========\n\n";

// Check all expenses in group
echo "All Expenses in Group 14:\n";
$allExpenses = Expense::where('group_id', 14)->get();
echo "Total expenses: " . count($allExpenses) . "\n";

foreach ($allExpenses as $exp) {
    echo "  - ID {$exp->id}: {$exp->description} (Payer: {$exp->payer_id}, Type: {$exp->split_type}, Amount: \${$exp->amount})\n";
}

echo "\n\nAll Advances in Group 14:\n";
$allAdvances = Advance::where('group_id', 14)->get();
echo "Total advances: " . count($allAdvances) . "\n";

foreach ($allAdvances as $adv) {
    $senderIds = $adv->senders->pluck('id')->toArray();
    echo "  - ID {$adv->id}: Advance from [" . implode(', ', $senderIds) . "] to {$adv->sent_to_user_id}, Amount: \${$adv->amount}\n";
}

echo "\n========== ARUN ↔ KARTHICK CALCULATION ==========\n\n";

// STEP 1: Find Arun spent (expenses paid by Arun where Karthick owes)
echo "STEP 1: Arun spent (payer=Arun, Karthick in split)\n";
$arunSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $arun)
    ->with(['splits'])
    ->get();

echo "  Arun paid " . count($expenses) . " expense(s)\n";
foreach ($expenses as $expense) {
    $karthickSplit = $expense->splits->where('user_id', $karthick)->first();
    if ($karthickSplit && $expense->split_type !== 'itemwise') {
        $arunSpent += $karthickSplit->share_amount;
        echo "    - {$expense->description}: \${$karthickSplit->share_amount}\n";
    } else {
        echo "    - {$expense->description}: Karthick NOT in split (or itemwise)\n";
    }
}
echo "  Total Arun Spent: \$$arunSpent\n\n";

// STEP 2: Find Karthick spent (expenses paid by Karthick where Arun owes)
echo "STEP 2: Karthick spent (payer=Karthick, Arun in split)\n";
$karthickSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $karthick)
    ->with(['splits'])
    ->get();

echo "  Karthick paid " . count($expenses) . " expense(s)\n";
foreach ($expenses as $expense) {
    $arunSplit = $expense->splits->where('user_id', $arun)->first();
    if ($arunSplit && $expense->split_type !== 'itemwise') {
        $karthickSpent += $arunSplit->share_amount;
        echo "    - {$expense->description}: \${$arunSplit->share_amount}\n";
    } else {
        echo "    - {$expense->description}: Arun NOT in split (or itemwise)\n";
    }
}
echo "  Total Karthick Spent: \$$karthickSpent\n\n";

// STEP 3: Find Arun send advance to Karthick
echo "STEP 3: Arun sent advance to Karthick\n";
$arunAdvanceToKarthick = 0;
$advances = Advance::where('group_id', 14)
    ->with(['senders'])
    ->get();

foreach ($advances as $advance) {
    $isArunSender = $advance->senders->where('id', $arun)->first() !== null;
    echo "  Advance {$advance->id}: Senders=[" . implode(', ', $advance->senders->pluck('id')->toArray()) . "], Sent To: {$advance->sent_to_user_id}, Amount: \${$advance->amount}\n";
    if ($isArunSender && $advance->sent_to_user_id == $karthick) {
        $arunAdvanceToKarthick = $advance->amount;
        echo "    ✓ This is Arun→Karthick advance\n";
    }
}
echo "  Total Arun Advance to Karthick: \$$arunAdvanceToKarthick\n\n";

// STEP 4: Find Karthick send advance to Arun
echo "STEP 4: Karthick sent advance to Arun\n";
$karthickAdvanceToArun = 0;
foreach ($advances as $advance) {
    $isKarthickSender = $advance->senders->where('id', $karthick)->first() !== null;
    if ($isKarthickSender && $advance->sent_to_user_id == $arun) {
        $karthickAdvanceToArun = $advance->amount;
        echo "  ✓ Karthick→Arun advance: \$$advance->amount\n";
    }
}
echo "  Total Karthick Advance to Arun: \$$karthickAdvanceToArun\n\n";

// STEP 5: Calculate final
echo "========== FINAL CALCULATION ==========\n";
echo "Formula: (Arun Spent - Karthick Spent) + (Arun Advance to Karthick) - (Karthick Advance to Arun)\n\n";

$result = ($arunSpent - $karthickSpent) + $arunAdvanceToKarthick - $karthickAdvanceToArun;

echo "= (\$$arunSpent - \$$karthickSpent) + \$$arunAdvanceToKarthick - \$$karthickAdvanceToArun\n";
echo "= \$" . ($arunSpent - $karthickSpent) . " + \$$arunAdvanceToKarthick - \$$karthickAdvanceToArun\n";
echo "= \$$result\n\n";

if ($result > 0) {
    echo "✅ Result: Arun owes Karthick \$$result\n";
} elseif ($result < 0) {
    echo "✅ Result: Karthick owes Arun \$" . abs($result) . "\n";
} else {
    echo "✅ Result: Settlement complete - \$0\n";
}

echo "\n";
