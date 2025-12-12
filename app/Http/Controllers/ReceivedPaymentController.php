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
        if ($validated['amount'] > $remainingOwed) {
            return redirect()->back()
                ->withErrors(['amount' => "Amount cannot exceed $" . round($remainingOwed, 2) . " owed. You've already received $" . round($alreadyReceived, 2) . "."])
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

        // Get all expenses where targetUser is the payer
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
                // Only count unpaid splits
                if (!$userSplit->payment || $userSplit->payment->status !== 'paid') {
                    $amountOwed += $userSplit->share_amount;
                }
            }
        }

        // Get targetUser's family count for received payment adjustment
        $targetUserFamilyCount = $group->members()
            ->where('user_id', $targetUser->id)
            ->first()
            ?->pivot
            ?->family_count ?? 1;

        if ($targetUserFamilyCount <= 0) {
            $targetUserFamilyCount = 1;
        }

        // Account for advances that benefit the current user
        // Advances are divided by sender's family count to get per-person credit
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        $totalAdvanceCredit = 0;

        foreach ($advances as $advance) {
            // Get all senders of this advance
            $senders = $advance->senders;

            foreach ($senders as $sender) {
                // Get sender's family count
                $senderFamilyCount = $group->members()
                    ->where('user_id', $sender->id)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;

                if ($senderFamilyCount <= 0) {
                    $senderFamilyCount = 1;
                }

                // Advance amount divided by sender's family count = per-person credit
                $perPersonCredit = $advance->amount_per_person / $senderFamilyCount;

                // Get current user's family count for their advance credit
                $userFamilyCount = $group->members()
                    ->where('user_id', $user->id)
                    ->first()
                    ?->pivot
                    ?->family_count ?? 1;
                if ($userFamilyCount <= 0) $userFamilyCount = 1;

                // Each person's advance credit = per-person-credit × their family count
                $userAdvanceCredit = $perPersonCredit * $userFamilyCount;
                $totalAdvanceCredit += $userAdvanceCredit;
            }
        }

        // Reduce the amount owed by advance credit
        $amountOwed -= $totalAdvanceCredit;

        // Ensure we don't have a negative amount owed
        if ($amountOwed < 0) {
            $amountOwed = 0;
        }

        return $amountOwed;
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
