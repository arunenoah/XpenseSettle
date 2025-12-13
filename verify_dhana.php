<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Expense;
use App\Models\Advance;
use App\Models\Group;

$group = Group::find(14);
$arun = 8;
$dhana = 10;

echo "\n========== ARUN ↔ DHANA CALCULATION ==========\n\n";

// STEP 1: Arun spent (on Dhana's share)
echo "STEP 1: Arun spent (payer=Arun, Dhana in split)\n";
$arunSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $arun)
    ->with(['splits'])
    ->get();

foreach ($expenses as $expense) {
    $dhanaSplit = $expense->splits->where('user_id', $dhana)->first();
    if ($dhanaSplit && $expense->split_type !== 'itemwise') {
        $arunSpent += $dhanaSplit->share_amount;
        echo "  - {$expense->description}: \${$dhanaSplit->share_amount}\n";
    }
}
echo "  Total Arun Spent: \$$arunSpent\n\n";

// STEP 2: Dhana spent (on Arun's share)
echo "STEP 2: Dhana spent (payer=Dhana, Arun in split)\n";
$dhanaSpent = 0;
$expenses = Expense::where('group_id', 14)
    ->where('payer_id', $dhana)
    ->with(['splits'])
    ->get();

foreach ($expenses as $expense) {
    $arunSplit = $expense->splits->where('user_id', $arun)->first();
    if ($arunSplit && $expense->split_type !== 'itemwise') {
        $dhanaSpent += $arunSplit->share_amount;
        echo "  - {$expense->description}: \${$arunSplit->share_amount}\n";
    }
}
echo "  Total Dhana Spent: \$$dhanaSpent\n\n";

// STEP 3: Arun sent advance to Dhana
echo "STEP 3: Arun sent advance to Dhana\n";
$arunAdvanceToDhana = 0;
$advances = Advance::where('group_id', 14)->with(['senders'])->get();

foreach ($advances as $advance) {
    $isArunSender = $advance->senders->where('id', $arun)->first() !== null;
    if ($isArunSender && $advance->sent_to_user_id == $dhana) {
        // Calculate Arun's advance credit
        $arunFamilyCount = $group->members()
            ->where('user_id', $arun)
            ->first()
            ?->pivot
            ?->family_count ?? 1;

        $perPersonCredit = $advance->amount_per_person / $arunFamilyCount;
        $arunCredit = $perPersonCredit * $arunFamilyCount;

        $arunAdvanceToDhana = $arunCredit;
        echo "  - Advance {$advance->id} to Dhana: \$" . $advance->amount_per_person . " → Arun's credit: \$$arunCredit\n";
    }
}
echo "  Total Arun Advance to Dhana: \$$arunAdvanceToDhana\n\n";

// STEP 4: Dhana send advance to Arun
echo "STEP 4: Dhana sent advance to Arun\n";
$dhanaAdvanceToArun = 0;
foreach ($advances as $advance) {
    $isDhanaSender = $advance->senders->where('id', $dhana)->first() !== null;
    if ($isDhanaSender && $advance->sent_to_user_id == $arun) {
        $dhanaFamilyCount = $group->members()
            ->where('user_id', $dhana)
            ->first()
            ?->pivot
            ?->family_count ?? 1;

        $perPersonCredit = $advance->amount_per_person / $dhanaFamilyCount;
        $dhanaCredit = $perPersonCredit * $dhanaFamilyCount;

        $dhanaAdvanceToArun = $dhanaCredit;
        echo "  - Advance {$advance->id} to Arun: \$" . $advance->amount_per_person . " → Dhana's credit: \$$dhanaCredit\n";
    }
}
echo "  Total Dhana Advance to Arun: \$$dhanaAdvanceToArun\n\n";

// Calculate final
echo "========== FINAL CALCULATION ==========\n";
echo "Formula: (Arun Spent - Dhana Spent) + (Arun Advance to Dhana) - (Dhana Advance to Arun)\n\n";

$result = ($arunSpent - $dhanaSpent) + $arunAdvanceToDhana - $dhanaAdvanceToArun;

echo "= (\$$arunSpent - \$$dhanaSpent) + \$$arunAdvanceToDhana - \$$dhanaAdvanceToArun\n";
echo "= \$" . ($arunSpent - $dhanaSpent) . " + \$$arunAdvanceToDhana - \$$dhanaAdvanceToArun\n";
echo "= \$$result\n\n";

if ($result > 0) {
    echo "Result: Arun owes Dhana \$$result\n";
} elseif ($result < 0) {
    echo "Result: Dhana owes Arun \$" . abs($result) . "\n";
} else {
    echo "Result: Settlement complete - \$0\n";
}

echo "\nExpected by user: \$149.88\n";
echo "Matches: " . (abs($result - 149.88) < 0.01 ? "✅ YES" : "❌ NO") . "\n\n";
