<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Advance;
use App\Models\Group;

$group = Group::find(14);

echo "\n========== ADVANCE ANALYSIS FOR GROUP 14 ==========\n\n";

$advances = Advance::where('group_id', 14)->get();

foreach ($advances as $advance) {
    echo "Advance {$advance->id}:\n";
    echo "  Sent To: {$advance->sent_to_user_id}\n";
    echo "  Amount Per Person: \${$advance->amount_per_person}\n";
    echo "  Senders:\n";

    $senders = $advance->senders;
    foreach ($senders as $sender) {
        $familyCount = $group->members()
            ->where('user_id', $sender->id)
            ->first()
            ?->pivot
            ?->family_count ?? 1;

        // Current logic in PaymentController
        $perPersonCredit = $advance->amount_per_person / $familyCount;
        $senderCredit = $perPersonCredit * $familyCount;

        echo "    - User {$sender->id}: Family Count = {$familyCount}\n";
        echo "      Per-person credit = \${$advance->amount_per_person} / {$familyCount} = \$" . round($perPersonCredit, 2) . "\n";
        echo "      Sender advance credit = \$" . round($perPersonCredit, 2) . " × {$familyCount} = \$" . round($senderCredit, 2) . "\n";
    }

    echo "\n";
}

echo "========== ARUN ↔ KARTHICK WITH ADVANCES ==========\n\n";

$arun = 8;
$karthick = 11;

// From verify_calculation.php, we know:
$arunSpent = 250.98;      // Karthick's share of room booking paid by Arun
$karthickSpent = 83.66;   // Arun's share of expense 420 paid by Karthick

echo "Arun spent (on Karthick's share): \$$arunSpent\n";
echo "Karthick spent (on Arun's share): \$$karthickSpent\n";
echo "Net before advances: \$" . ($arunSpent - $karthickSpent) . "\n\n";

// Advance 2: From [8, 9, 12] to 11 (Karthick)
echo "Advance 2 (From Arun/Velu/Param to Karthick):\n";
echo "  Amount per person: \$101\n";
echo "  Arun is a sender\n";

$arunFamilyCount = 4;
$perPersonCredit = 101 / $arunFamilyCount;
$arunAdvanceCredit = $perPersonCredit * $arunFamilyCount;
echo "  Arun's advance credit: " . round($perPersonCredit, 2) . " × " . $arunFamilyCount . " = \$" . round($arunAdvanceCredit, 2) . "\n";
echo "  (This is ARUN's payment to Karthick, not received BY Arun)\n\n";

// Check: is Arun → Karthick or Karthick → Arun?
// If Arun SENT the advance to Karthick, this reduces Arun's debt to Karthick

$result = ($arunSpent - $karthickSpent) - $arunAdvanceCredit;

echo "Final calculation for Arun ↔ Karthick:\n";
echo "= (\$$arunSpent - \$$karthickSpent) - \$$arunAdvanceCredit\n";
echo "= \$" . ($arunSpent - $karthickSpent) . " - \$" . round($arunAdvanceCredit, 2) . "\n";
echo "= \$" . round($result, 2) . "\n\n";

if ($result > 0) {
    echo "Result: Arun owes Karthick \$" . round($result, 2) . "\n";
} elseif ($result < 0) {
    echo "Result: Karthick owes Arun \$" . round(abs($result), 2) . "\n";
} else {
    echo "Result: Settlement complete\n";
}

echo "\n";
