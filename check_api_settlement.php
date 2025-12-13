<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\PaymentController;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

// Set authenticated user to Arun (user 8)
auth()->setUser(User::find(8));

$group = Group::find(14);
$user = auth()->user();

echo "\n========== SETTLEMENT CALCULATION (via PaymentController) ==========\n";
echo "Authenticated as: {$user->name} (ID: {$user->id})\n\n";

// Create a controller instance
$controller = new PaymentController();

// We'll call the calculateSettlement method directly
// But it's private, so let me access it via reflection
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calculateSettlement');
$method->setAccessible(true);

$relatedUsers = [9, 10, 11, 12, 13, 14]; // Velu, Dhana, Karthick, Param, Mohan, Ganesh

foreach ($relatedUsers as $relatedUserId) {
    $result = $method->invoke($controller, $group, $user, User::find($relatedUserId));
    $relatedUser = User::find($relatedUserId);
    echo "{$user->name} ↔ {$relatedUser->name}:\n";
    echo "  Net amount: \${$result['net_amount']}\n";
    if (abs($result['net_amount']) < 0.01) {
        echo "  Status: ✅ Settled\n";
    } elseif ($result['net_amount'] > 0) {
        echo "  Status: {$user->name} owes {$relatedUser->name}\n";
    } else {
        echo "  Status: {$relatedUser->name} owes {$user->name}\n";
    }
    echo "\n";
}
