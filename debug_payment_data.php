<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Group;
use App\Models\Payment;
use App\Models\ExpenseSplit;

echo "=== DEBUGGING MARK AS PAID DATA ISSUES ===\n\n";

// Find Arun
$arun = User::where('name', 'Arun')->first();

if (!$arun) {
    echo "❌ Could not find Arun\n";
    exit(1);
}

echo "User: Arun (ID: {$arun->id})\n\n";

// Get all groups Arun is in
$groups = $arun->groups;

echo "=== GROUPS ({$groups->count()}) ===\n\n";

foreach ($groups as $group) {
    echo "📁 Group: {$group->name} (ID: {$group->id})\n";
    echo "   Currency: {$group->currency}\n\n";
    
    // Get recent payments in this group
    $payments = Payment::whereHas('split.expense', function($q) use ($group) {
        $q->where('group_id', $group->id);
    })
    ->with('split.expense.payer', 'split.user')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
    
    echo "   Recent Payments ({$payments->count()}):\n";
    foreach ($payments as $payment) {
        $splitAmount = $payment->split->share_amount ?? 0;
        $from = $payment->split->user ? $payment->split->user->name : 'Contact';
        $to = $payment->split->expense->payer->name ?? 'Unknown';
        
        echo "   • Payment ID: {$payment->id}\n";
        echo "     Split ID: {$payment->expense_split_id}\n";
        echo "     Split Amount: \${$splitAmount}\n";
        echo "     From: {$from} → To: {$to}\n";
        echo "     Expense: {$payment->split->expense->title}\n";
        echo "     Status: {$payment->status}\n";
        echo "     Created: {$payment->created_at}\n";
        
        if ($splitAmount == 0) {
            echo "     ⚠️  WARNING: Split amount is ZERO!\n";
        }
        echo "\n";
    }
    
    // Check for splits with zero amounts
    $zeroSplits = ExpenseSplit::whereHas('expense', function($q) use ($group) {
        $q->where('group_id', $group->id);
    })
    ->where('share_amount', 0)
    ->with('expense', 'user', 'payment')
    ->get();
    
    if ($zeroSplits->count() > 0) {
        echo "   ⚠️  ZERO AMOUNT SPLITS ({$zeroSplits->count()}):\n";
        foreach ($zeroSplits as $split) {
            $hasPayment = $split->payment ? "Yes (Status: {$split->payment->status})" : "No";
            $userName = $split->user ? $split->user->name : 'Contact';
            echo "   • Split ID: {$split->id}\n";
            echo "     User: {$userName}\n";
            echo "     Expense: {$split->expense->title}\n";
            echo "     Amount: \${$split->share_amount}\n";
            echo "     Has Payment: {$hasPayment}\n\n";
        }
    }
    
    // Check settlement for Arun
    echo "   Settlement for Arun:\n";
    $controller = new \App\Http\Controllers\PaymentController(
        app('App\Services\PaymentService'),
        app('App\Services\AttachmentService'),
        app('App\Services\NotificationService'),
        app('App\Services\AuditService')
    );
    
    $settlement = $controller->calculateSettlement($group, $arun);
    
    foreach ($settlement as $item) {
        $status = $item['net_amount'] > 0 ? 'You Owe' : 'They Owe You';
        echo "   • {$item['user']->name}: \${$item['amount']} ({$status})\n";
        echo "     Split IDs: " . implode(', ', $item['split_ids']) . "\n";
        echo "     Expenses: " . count($item['expenses']) . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "✅ Debug complete!\n";
