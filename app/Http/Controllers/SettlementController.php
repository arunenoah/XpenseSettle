<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\SettlementConfirmation;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    /**
     * Confirm a settlement payment between two users.
     * Called when "Mark as Paid" is submitted from the Trip Summary page.
     */
    public function confirmSettlement(Group $group, Request $request)
    {
        // Check user is a group member
        if (!$group->hasMember(auth()->user())) {
            return response()->json(['error' => 'Not a group member'], 403);
        }

        // Validate input
        $validated = $request->validate([
            'from_user_id' => 'required|integer|exists:users,id',
            'to_user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        // Ensure both users are group members
        if (!$group->members()->where('user_id', $validated['from_user_id'])->exists()) {
            return response()->json(['error' => 'From user not in group'], 422);
        }
        if (!$group->members()->where('user_id', $validated['to_user_id'])->exists()) {
            return response()->json(['error' => 'To user not in group'], 422);
        }

        // Create settlement confirmation
        $settlement = SettlementConfirmation::create([
            'group_id' => $group->id,
            'from_user_id' => $validated['from_user_id'],
            'to_user_id' => $validated['to_user_id'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'],
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        // Handle photo upload if provided
        if ($request->hasFile('photo')) {
            $settlement->attachments()->create([
                'file_path' => $request->file('photo')->store("settlements/{$group->id}", 'public'),
                'file_name' => $request->file('photo')->getClientOriginalName(),
                'file_type' => $request->file('photo')->getMimeType(),
                'file_size' => $request->file('photo')->getSize(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settlement confirmed successfully',
            'settlement' => $settlement,
        ]);
    }

    /**
     * Get settlement confirmations history for a group.
     */
    public function getSettlementHistory(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'Not a group member');
        }

        $settlements = SettlementConfirmation::where('group_id', $group->id)
            ->with(['fromUser', 'toUser', 'confirmedBy', 'attachments'])
            ->latest('confirmed_at')
            ->get();

        return response()->json($settlements);
    }

    /**
     * Get unsettled transactions from Trip Summary.
     * These are the settlements that are pending confirmation.
     */
    public function getUnsettledTransactions(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'Not a group member');
        }

        // Get current settlement calculation from DashboardController
        $user = auth()->user();
        $dashboardController = new \App\Http\Controllers\DashboardController(
            app('App\Services\PaymentService')
        );

        // This is a simplified version - in production you'd use the groupSummary logic
        // to get the minimal settlement list
        $expenses = $group->expenses()
            ->where('split_type', '!=', 'itemwise')
            ->with('payer', 'splits.user', 'splits.payment')
            ->get();

        // Get confirmed settlements
        $confirmed = SettlementConfirmation::where('group_id', $group->id)
            ->get()
            ->keyBy(fn($s) => "{$s->from_user_id}-{$s->to_user_id}");

        return response()->json([
            'total_expenses' => $expenses->count(),
            'confirmed_count' => $confirmed->count(),
            'confirmed_settlements' => $confirmed->values(),
        ]);
    }
}
