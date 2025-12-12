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
        if (!$group->hasMember(User::find($validated['from_user_id']))) {
            abort(403, 'User is not a member of this group');
        }

        ReceivedPayment::create([
            'group_id' => $group->id,
            'from_user_id' => $validated['from_user_id'],
            'to_user_id' => auth()->user()->id,
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
