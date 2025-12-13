<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Expense;
use App\Models\Advance;
use App\Models\Group;
use App\Models\User;

$group = Group::find(14);

echo "\n========== SETTLEMENT SUMMARY ==========\n\n";

echo "✅ FIXES APPLIED:\n";
echo "1. Fixed 3 problematic expenses: 416→421, 419→422, 420→423\n";
echo "2. Deleted 5 incorrect received payments that were offsetting settlements\n\n";

echo "========== CURRENT EXPENSES ==========\n";
$expenses = Expense::where('group_id', 14)->get();
echo "Total: " . count($expenses) . " expenses\n";
foreach ($expenses as $exp) {
    echo "  - {$exp->id}: {$exp->description} (\${$exp->amount}) - Payer: {$exp->payer_id}\n";
}

echo "\n========== CURRENT ADVANCES ==========\n";
$advances = Advance::where('group_id', 14)->get();
foreach ($advances as $adv) {
    $senderIds = $adv->senders->pluck('id')->toArray();
    echo "  - Advance {$adv->id}: From [" . implode(', ', $senderIds) . "] to {$adv->sent_to_user_id}, Amount/Person: \${$adv->amount_per_person}\n";
}

echo "\n========== CALCULATION FOR ARUN ↔ KARTHICK ==========\n\n";

$arun = 8;
$karthick = 11;

// Step 1: Arun spent
$arunSpent = Expense::where('group_id', 14)
    ->where('payer_id', $arun)
    ->with('splits')
    ->get()
    ->flatMap(fn($e) => $e->splits->where('user_id', $karthick)->pluck('share_amount'))
    ->sum();

echo "1. Arun paid for Karthick's share: \$$arunSpent\n";

// Step 2: Karthick spent
$karthickSpent = Expense::where('group_id', 14)
    ->where('payer_id', $karthick)
    ->with('splits')
    ->get()
    ->flatMap(fn($e) => $e->splits->where('user_id', $arun)->pluck('share_amount'))
    ->sum();

echo "2. Karthick paid for Arun's share: \$$karthickSpent\n";

// Step 3: Advances
echo "3. Advances:\n";

$arunAdvanceToKarthick = 0;
foreach ($advances as $adv) {
    if ($adv->sent_to_user_id == $karthick && $adv->senders->where('id', $arun)->first()) {
        $arunAdvanceToKarthick = $adv->amount_per_person;
        echo "   - Arun sent \${$adv->amount_per_person} to Karthick\n";
    }
}

$karthickAdvanceToArun = 0;
foreach ($advances as $adv) {
    if ($adv->sent_to_user_id == $arun && $adv->senders->where('id', $karthick)->first()) {
        $karthickAdvanceToArun = $adv->amount_per_person;
        echo "   - Karthick sent \${$adv->amount_per_person} to Arun\n";
    }
}

echo "\nFormula: (Arun Spent - Karthick Spent) + (Arun Advances to Karthick) - (Karthick Advances to Arun)\n";
echo "= (\$$arunSpent - \$$karthickSpent) + \$$arunAdvanceToKarthick - \$$karthickAdvanceToArun\n";

$result = ($arunSpent - $karthickSpent) + $arunAdvanceToKarthick - $karthickAdvanceToArun;

echo "= \$" . ($arunSpent - $karthickSpent) . " + \$$arunAdvanceToKarthick - \$$karthickAdvanceToArun\n";
echo "= \$" . $result . "\n\n";

if ($result > 0) {
    echo "RESULT: Arun owes Karthick \$$result\n";
} elseif ($result < 0) {
    echo "RESULT: Karthick owes Arun \$" . abs($result) . "\n";
} else {
    echo "RESULT: Settlement complete\n";
}

echo "\nExpected by user: \$17.34\n";
echo "\n❓ QUESTION: Does this match your calculation?\n";
echo "   If not, please clarify what the breakdown should be.\n\n";
