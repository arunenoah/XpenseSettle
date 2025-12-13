<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Group;
use App\Models\User;
use App\Models\Expense;
use App\Models\Advance;
use App\Models\ReceivedPayment;
use Illuminate\Support\Facades\DB;

echo "\n========== DIRECT SETTLEMENT CALCULATION ==========\n\n";

$groupId = 14;
$userId = 8;  // Arun

$group = Group::find($groupId);
$user = User::find($userId);

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Group: {$group->name}\n\n";

// For each other member, calculate settlement
$members = $group->members;

foreach ($members as $member) {
    $relatedUserId = $member->id;
    if ($relatedUserId === $userId) continue;

    $relatedUser = User::find($relatedUserId);

    // ======== PART 1: EXPENSES ========
    $amountOwed = 0;

    // User owes relatedUser
    $expenses = Expense::where('group_id', $groupId)
        ->where('payer_id', $relatedUserId)
        ->with(['splits' => function ($q) {
            $q->with(['payment']);
        }])
        ->get();

    foreach ($expenses as $expense) {
        if ($expense->split_type === 'itemwise') continue;

        $userSplit = $expense->splits->where('user_id', $userId)->first();
        if ($userSplit) {
            if (!$userSplit->payment || $userSplit->payment->status !== 'paid') {
                $amountOwed += $userSplit->share_amount;
            }
        }
    }

    // RelatedUser owes user (subtract)
    $userPaidExpenses = Expense::where('group_id', $groupId)
        ->where('payer_id', $userId)
        ->with(['splits' => function ($q) {
            $q->with(['payment']);
        }])
        ->get();

    foreach ($userPaidExpenses as $expense) {
        if ($expense->split_type === 'itemwise') continue;

        $relatedUserSplit = $expense->splits->where('user_id', $relatedUserId)->first();
        if ($relatedUserSplit) {
            if (!$relatedUserSplit->payment || $relatedUserSplit->payment->status !== 'paid') {
                $amountOwed -= $relatedUserSplit->share_amount;
            }
        }
    }

    echo "=== {$user->name} ↔ {$relatedUser->name} ===\n";
    echo "Expenses owed (before advances): \$$amountOwed\n";

    // ======== PART 2: ADVANCES ========
    $advances = Advance::where('group_id', $groupId)->with(['senders'])->get();

    foreach ($advances as $advance) {
        $recipientId = $advance->sent_to_user_id;
        $senders = $advance->senders;

        foreach ($senders as $sender) {
            $senderId = $sender->id;

            $senderFamilyCount = $group->members()
                ->where('user_id', $senderId)
                ->first()
                ?->pivot
                ?->family_count ?? 1;

            $perPersonCredit = $advance->amount_per_person / $senderFamilyCount;
            $senderAdvanceCredit = $perPersonCredit * $senderFamilyCount;

            // CASE 1: User is the sender
            if ($senderId === $userId && $recipientId === $relatedUserId) {
                echo "  - Arun sent \$" . round($senderAdvanceCredit, 2) . " to " . $relatedUser->name . "\n";
                if ($amountOwed >= 0) {
                    $amountOwed -= $senderAdvanceCredit;
                } else {
                    $amountOwed += $senderAdvanceCredit;
                }
            }
            // CASE 2: User is the recipient
            elseif ($senderId === $relatedUserId && $recipientId === $userId) {
                echo "  - " . $relatedUser->name . " sent \$" . round($senderAdvanceCredit, 2) . " to Arun\n";
                if ($amountOwed >= 0) {
                    $amountOwed -= $senderAdvanceCredit;
                } else {
                    $amountOwed += $senderAdvanceCredit;
                }
            }
        }
    }

    // ======== PART 3: PAYMENTS ========
    $receivedPayments = ReceivedPayment::where('group_id', $groupId)
        ->where('to_user_id', $userId)
        ->get();

    foreach ($receivedPayments as $p) {
        $fromUserId = $p->from_user_id;
        if ($fromUserId === $relatedUserId) {
            echo "  - Received \$" . round($p->amount, 2) . " from " . $relatedUser->name . "\n";
            $amountOwed -= $p->amount;
        }
    }

    $sentPayments = ReceivedPayment::where('group_id', $groupId)
        ->where('from_user_id', $userId)
        ->get();

    foreach ($sentPayments as $p) {
        $toUserId = $p->to_user_id;
        if ($toUserId === $relatedUserId) {
            echo "  - Sent \$" . round($p->amount, 2) . " to " . $relatedUser->name . "\n";
            $amountOwed -= $p->amount;
        }
    }

    echo "Final Settlement: \$$amountOwed\n\n";
}

echo "\n";
