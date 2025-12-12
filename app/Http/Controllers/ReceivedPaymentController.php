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

        // Calculate amount this user owes to the payer (from_user)
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
        $amountOwed = 0;

        // Get expenses where from_user is payer and current user owes
        $expenses = \App\Models\Expense::where('group_id', $group->id)
            ->where('payer_id', $fromUser->id)
            ->with(['splits' => function ($q) use ($user) {
                $q->with(['payment', 'user']);
                $q->where('user_id', $user->id); // Only get splits for current user
            }])
            ->get();

        foreach ($expenses as $expense) {
            if ($expense->splits->isNotEmpty()) {
                foreach ($expense->splits as $split) {
                    // Only count unpaid splits
                    if (!$split->payment || $split->payment->status !== 'paid') {
                        $amountOwed += $split->share_amount;
                    }
                }
            }
        }

        // Get payer's family count for adjustment
        $payerFamilyCount = $group->members()
            ->where('user_id', $fromUser->id)
            ->first()
            ?->pivot
            ?->family_count ?? 1;

        if ($payerFamilyCount <= 0) {
            $payerFamilyCount = 1;
        }

        // Subtract any received payments already recorded
        // Received payments are also adjusted by payer's family count
        $receivedPaymentsRaw = ReceivedPayment::where('group_id', $group->id)
            ->where('from_user_id', $fromUser->id)
            ->where('to_user_id', $user->id)
            ->get();

        $alreadyReceived = 0;
        foreach ($receivedPaymentsRaw as $payment) {
            $alreadyReceived += $payment->amount / $payerFamilyCount;
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
