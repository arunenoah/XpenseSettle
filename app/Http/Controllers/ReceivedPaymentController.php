<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\ReceivedPayment;
use App\Models\User;
use Illuminate\Http\Request;

class ReceivedPaymentController extends Controller
{
    /**
     * Store a newly created received payment.
     */
    public function store(Request $request, Group $group)
    {
        // Authorize user is member of group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'Not a member of this group');
        }

        $validated = $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'received_date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ]);

        // Verify the from_user is a member of the group
        $fromUser = User::find($validated['from_user_id']);
        if (!$group->hasMember($fromUser)) {
            abort(403, 'User is not a member of this group');
        }

        // Load necessary relationships for settlement calculation
        $group->load([
            'expenses' => function ($query) {
                $query->latest();
            },
            'expenses.splits.user',
            'expenses.splits.contact',
            'expenses.splits.payment',
            'expenses.payer',
            'members',
            'contacts'
        ]);

        // Calculate settlement between the authenticated user and the from_user
        $user = auth()->user();

        // Calculate amount owed from user to fromUser using full settlement logic
        $amountOwed = $this->calculateAmountOwedToUser($group, $user, $fromUser);

        // Get already received payments from this user
        $receivedPaymentsRaw = ReceivedPayment::where('group_id', $group->id)
            ->where('from_user_id', $fromUser->id)
            ->where('to_user_id', $user->id)
            ->get();

        $alreadyReceived = 0;
        foreach ($receivedPaymentsRaw as $payment) {
            $alreadyReceived += $payment->amount;
        }

        $remainingOwed = $amountOwed - $alreadyReceived;

        // Validate that the amount doesn't exceed what is owed
        // Note: amountOwed can be negative (they owe us) or positive (we owe them)
        // If negative, they owe us money, so we can receive any amount up to abs(amountOwed) - alreadyReceived
        if ($amountOwed < 0) {
            // They owe us money
            // We can receive up to the absolute amount they owe minus what we've already received
            $maxCanReceive = abs($amountOwed) - $alreadyReceived;
            if ($validated['amount'] > $maxCanReceive) {
                return redirect()->back()
                    ->withErrors(['amount' => "Amount cannot exceed $" . round($maxCanReceive, 2) . " owed to you. You've already received $" . round($alreadyReceived, 2) . "."])
                    ->withInput();
            }
        } else {
            // We owe them money, should not be recording as received
            return redirect()->back()
                ->withErrors(['amount' => "You owe this person money, you cannot record receiving payment from them. Use 'Mark as Paid' instead."])
                ->withInput();
        }

        ReceivedPayment::create([
            'group_id' => $group->id,
            'from_user_id' => $validated['from_user_id'],
            'to_user_id' => $user->id,
            'amount' => $validated['amount'],
            'received_date' => $validated['received_date'],
            'description' => $validated['description'] ?? null,
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'Payment received recorded successfully!');
    }

    /**
     * Calculate how much the current user owes to another specific user in the group.
     * This uses the same logic as calculateSettlement but filters for one person.
     */
    private function calculateAmountOwedToUser($group, $user, $targetUser)
    {
        $amountOwed = 0;

        // PART 1: Get all expenses where targetUser is the payer
        // User owes targetUser for these expenses
        $expenses = \App\Models\Expense::where('group_id', $group->id)
            ->where('payer_id', $targetUser->id)
            ->with([
                'splits' => function ($q) {
                    $q->with(['payment', 'user']);
                },
                'payer'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($expenses as $expense) {
            // Skip itemwise expenses
            if ($expense->split_type === 'itemwise') {
                continue;
            }

            // Find the user's split in this expense
            $userSplit = $expense->splits->where('user_id', $user->id)->first();

            if ($userSplit) {
                // Count all splits regardless of payment status
                // Individual payments don't eliminate the split from settlement calculations
                $amountOwed += $userSplit->share_amount;
            }
        }

        // PART 2: Get all expenses where user is the payer
        // targetUser owes user for these expenses (subtract from amount owed)
        $userPaidExpenses = \App\Models\Expense::where('group_id', $group->id)
            ->where('payer_id', $user->id)
            ->with([
                'splits' => function ($q) {
                    $q->with(['payment', 'user']);
                },
                'payer'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($userPaidExpenses as $expense) {
            // Skip itemwise expenses
            if ($expense->split_type === 'itemwise') {
                continue;
            }

            // Find the targetUser's split in this expense
            $targetUserSplit = $expense->splits->where('user_id', $targetUser->id)->first();

            if ($targetUserSplit) {
                // Count all splits regardless of payment status
                // Individual payments don't eliminate the split from settlement calculations
                // Subtract: targetUser owes user for this (reduces amount owed TO targetUser)
                $amountOwed -= $targetUserSplit->share_amount;
            }
        }

        // Account for advances paid by current user
        // Only apply if user is a sender of the advance (they paid it)
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        $totalAdvanceCredit = 0;

        foreach ($advances as $advance) {
            $senders = $advance->senders;

            // Check if current user is a sender of this advance
            $isUserASender = false;
            foreach ($senders as $sender) {
                if ($sender->id === $user->id) {
                    $isUserASender = true;
                    break;
                }
            }

            if ($isUserASender) {
                // Get current user's family count
                $userFamilyCount = $group->members()
                    ->where('user_id', $user->id)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;
                if ($userFamilyCount <= 0) $userFamilyCount = 1;

                // Advance amount divided by sender's family count = per-person credit
                $perPersonCredit = $advance->amount_per_person / $userFamilyCount;

                // User's advance credit = per-person-credit × their family count
                $userAdvanceCredit = $perPersonCredit * $userFamilyCount;
                $totalAdvanceCredit += $userAdvanceCredit;
            }
        }

        // Reduce the amount owed by advance credit (user paid this advance)
        $amountOwed -= $totalAdvanceCredit;

        // Convert to absolute value: when negative, it means targetUser owes user (which is what we want for "payment received")
        // When positive, it means user owes targetUser (which would be 0 for "payment received" validation)
        return abs($amountOwed);
    }

    /**
     * Delete a received payment.
     */
    public function destroy(Group $group, ReceivedPayment $receivedPayment)
    {
        // Authorize user is the recipient or admin
        if (auth()->user()->id !== $receivedPayment->to_user_id && !$group->isAdmin(auth()->user())) {
            abort(403, 'Unauthorized');
        }

        $receivedPayment->delete();

        return redirect()->back()->with('success', 'Payment record deleted!');
    }

    /**
     * Get received payments for a member in a group.
     */
    public function getForMember(Group $group, User $user)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'Not a member of this group');
        }

        return ReceivedPayment::where('group_id', $group->id)
            ->where('to_user_id', $user->id)
            ->orderBy('received_date', 'desc')
            ->get();
    }
}
