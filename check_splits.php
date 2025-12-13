<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

echo "\n========== CHECKING ROOM BOOKING EXPENSE SPLITS ==========\n\n";

$expense = Expense::find(419); // Balance amount for room booking

echo "Expense 419 Details:\n";
echo "  Payer ID: {$expense->payer_id} (Arun)\n";
echo "  Amount: \${$expense->amount}\n";
echo "  Split Type: {$expense->split_type}\n";
echo "  Total splits: " . $expense->splits->count() . "\n\n";

echo "All Splits for Expense 419:\n";
$totalSplits = 0;
foreach ($expense->splits as $split) {
    echo "  User {$split->user_id}: \${$split->share_amount}\n";
    $totalSplits += $split->share_amount;
}
echo "  Total: \$$totalSplits\n";

echo "\n\nGroup 14 Members:\n";
$group = $expense->group;
foreach ($group->members as $member) {
    $familyCount = $member->pivot->family_count ?? 1;
    echo "  User {$member->id} ({$member->name}): Family Count = {$familyCount}\n";
}

echo "\n========== WHAT SHOULD HAPPEN ==========\n";
echo "Family-count-based split calculation:\n";
echo "  Total expense: \${$expense->amount}\n";
echo "  Total family count in group: ";

$totalFamilyCount = 0;
foreach ($group->members as $member) {
    $familyCount = $member->pivot->family_count ?? 1;
    $totalFamilyCount += $familyCount;
}
echo "$totalFamilyCount\n";

$perPersonCost = $expense->amount / $totalFamilyCount;
echo "  Per-person cost: \$" . round($perPersonCost, 2) . "\n\n";

foreach ($group->members as $member) {
    $familyCount = $member->pivot->family_count ?? 1;
    $perPersonCost = $expense->amount / $totalFamilyCount;
    $share = $perPersonCost * $familyCount;
    echo "  User {$member->id} ({$member->name}): {$familyCount} family × \$" . round($perPersonCost, 2) . " = \$" . round($share, 2) . "\n";
}

echo "\n";
