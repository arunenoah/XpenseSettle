<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Group;
use App\Models\Payment;

echo "Checking Payment Issue...\n\n";

// Find Arun and Karthick
$arun = User::where('name', 'Arun')->first();
$karthick = User::where('name', 'Karthick')->orWhere('name', 'Karthik')->first();

if (!$arun || !$karthick) {
    echo "❌ Could not find users\n";
    exit(1);
}

echo "Users found:\n";
echo "  Arun ID: {$arun->id}\n";
echo "  Karthick ID: {$karthick->id}\n\n";

// Find groups they're both in
$groups = $arun->groups()->whereHas('members', function($q) use ($karthick) {
    $q->where('user_id', $karthick->id);
})->get();

echo "Shared groups: {$groups->count()}\n\n";

foreach ($groups as $group) {
    echo "=== Group: {$group->name} ===\n";
    
    // Get recent payments in this group
    $payments = Payment::whereHas('split.expense', function($q) use ($group) {
        $q->where('group_id', $group->id);
    })
    ->with('split.expense', 'split.user')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();
    
    echo "Recent payments:\n";
    foreach ($payments as $payment) {
        echo "  Payment ID: {$payment->id}\n";
        echo "    Expense: {$payment->split->expense->title}\n";
        echo "    Split Amount: \${$payment->split->share_amount}\n";
        echo "    From: " . ($payment->split->user ? $payment->split->user->name : 'Contact') . "\n";
        echo "    To: {$payment->split->expense->payer->name}\n";
        echo "    Status: {$payment->status}\n";
        echo "    Created: {$payment->created_at}\n\n";
    }
    
    // Check splits where Arun owes Karthick
    $splits = \App\Models\ExpenseSplit::whereHas('expense', function($q) use ($group, $karthick) {
        $q->where('group_id', $group->id)
          ->where('payer_id', $karthick->id);
    })
    ->where('user_id', $arun->id)
    ->with('expense', 'payment')
    ->get();
    
    echo "Splits where Arun owes Karthick:\n";
    foreach ($splits as $split) {
        $paymentStatus = $split->payment ? $split->payment->status : 'no payment';
        echo "  Split ID: {$split->id}\n";
        echo "    Expense: {$split->expense->title}\n";
        echo "    Amount: \${$split->share_amount}\n";
        echo "    Payment Status: {$paymentStatus}\n\n";
    }
    
    echo "\n";
}
