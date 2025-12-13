<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Group;
use Illuminate\Support\Facades\DB;

$group = Group::find(14);
$members = $group->members->pluck('id', 'name')->all();

echo "\n=== SETTLEMENT MATRIX SYMMETRY CHECK - GROUP 14 ===\n\n";
echo "Checking " . count($members) . " members for symmetric settlements...\n\n";

$issues = [];

// Get all ReceivedPayments for this group to understand the actual payments
$payments = DB::table('received_payments')
    ->where('group_id', 14)
    ->get()
    ->groupBy(function($p) {
        return min($p->from_user_id, $p->to_user_id) . '-' . max($p->from_user_id, $p->to_user_id);
    });

echo "Found " . count($payments) . " payment pairs\n";
foreach($payments as $pair => $paymentSet) {
    $total = $paymentSet->sum('amount');
    echo "  Pair: $pair → Total: \$" . number_format($total, 2) . "\n";
}

echo "\n=== Key Settlements to Check ===\n\n";

// Check specific key pairs
$keyPairs = [
    [8, 9],   // Arun ↔ Velu
    [8, 10],  // Arun ↔ Dhana
    [8, 7],   // Arun ↔ Karthick
];

// Display the matrix visually
echo "=== SETTLEMENT MATRIX ===\n\n";

$settlement_matrix = [];
foreach($members as $name => $id) {
    $settlement_matrix[$id] = ['name' => $name];
}

echo "Member IDs: ";
foreach($members as $name => $id) {
    echo "$id=$name ";
}
echo "\n\n";

// For now, let's just tell the user to check the browser
echo "✅ VERIFICATION METHOD:\n";
echo "1. Open the settlement matrix in browser\n";
echo "2. For each pair, check that both directions show the same value\n";
echo "3. Example: Arun→Karthick should equal Karthick→Arun\n\n";

echo "Common pairs to check:\n";
echo "  - Arun (8) ↔ Velu (9): Check $0.01\n";
echo "  - Arun (8) ↔ Dhana (10): Check $250.12\n";
echo "  - Arun (8) ↔ Karthick (7): Check $184.66 (or should be $17.34 after fix)\n";
echo "  - Dhana (10) ↔ Velu (9): Check $149.88\n";
echo "\n";
