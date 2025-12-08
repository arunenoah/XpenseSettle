<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Group;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    /**
     * Store a newly created advance.
     */
    public function store(Request $request, Group $group)
    {
        // Check authorization
        if (!$group->hasMember(auth()->user())) {
            return redirect()->back()->with('error', 'You are not a member of this group');
        }

        $validated = $request->validate([
            'sent_to_user_id' => 'required|exists:users,id',
            'amount_per_person' => 'required|numeric|min:0.01',
            'senders' => 'required|array|min:1',
            'senders.*' => 'exists:users,id',
            'date' => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            // Create the advance record
            $advance = Advance::create([
                'group_id' => $group->id,
                'sent_to_user_id' => $validated['sent_to_user_id'],
                'amount_per_person' => $validated['amount_per_person'],
                'date' => $validated['date'],
                'description' => $validated['description'],
            ]);

            // Attach the senders
            $advance->senders()->attach($validated['senders']);

            // Log activity for timeline
            ActivityService::logAdvancePaid($group, $advance, $validated['senders']);

            return redirect()->back()->with('success', 'Advance recorded successfully! 💰');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to record advance: ' . $e->getMessage());
        }
    }

    /**
     * Remove an advance record.
     */
    public function destroy(Group $group, Advance $advance)
    {
        // Check authorization
        if ($advance->group_id !== $group->id) {
            abort(404);
        }

        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        try {
            $advance->delete();
            return redirect()->back()->with('success', 'Advance deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete advance: ' . $e->getMessage());
        }
    }
}
