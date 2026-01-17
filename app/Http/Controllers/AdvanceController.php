<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Group;
use App\Services\ActivityService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * API endpoint: Record an advance/prepayment for group members
     */
    public function apiStore(Request $request)
    {
        $groupId = $request->query('group_id') ?? $request->input('group_id');

        if (!$groupId) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: group_id',
                'status' => 400
            ];
        }

        $group = Group::find($groupId);
        if (!$group) {
            return [
                'success' => false,
                'message' => 'Group not found',
                'status' => 404
            ];
        }

        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403
            ];
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

            // Log advance recording
            $totalAmount = $validated['amount_per_person'] * count($validated['senders']);
            $this->auditService->logSuccess(
                'record_advance',
                'Advance',
                "Advance of {$totalAmount} recorded in group '{$group->name}'",
                $advance->id,
                $group->id
            );

            // Log activity for timeline
            ActivityService::logAdvancePaid($group, $advance, $validated['senders']);

            // Load relationships for response
            $advance->load('senders', 'sentTo');

            return [
                'success' => true,
                'data' => [
                    'id' => $advance->id,
                    'group_id' => $group->id,
                    'amount_per_person' => round($advance->amount_per_person, 2),
                    'total_amount' => round($validated['amount_per_person'] * count($validated['senders']), 2),
                    'date' => $advance->date,
                    'description' => $advance->description,
                    'created_at' => $advance->created_at,
                    'sent_to' => [
                        'id' => $advance->sentTo->id,
                        'name' => $advance->sentTo->name,
                        'email' => $advance->sentTo->email,
                    ],
                    'senders' => $advance->senders->map(function ($sender) {
                        return [
                            'id' => $sender->id,
                            'name' => $sender->name,
                            'email' => $sender->email,
                        ];
                    })->toArray(),
                    'sender_count' => count($validated['senders']),
                ],
                'message' => 'Advance recorded successfully',
                'status' => 201,
            ];
        } catch (\Exception $e) {
            // Log failed advance recording
            $this->auditService->logFailed(
                'record_advance',
                'Advance',
                'Failed to record advance',
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Failed to record advance: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

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

            // Log advance recording
            $totalAmount = $validated['amount_per_person'] * count($validated['senders']);
            $this->auditService->logSuccess(
                'record_advance',
                'Advance',
                "Advance of {$totalAmount} recorded in group '{$group->name}'",
                $advance->id,
                $group->id
            );

            // Log activity for timeline
            ActivityService::logAdvancePaid($group, $advance, $validated['senders']);

            return redirect()->back()->with('success', 'Advance recorded successfully! 💰');
        } catch (\Exception $e) {
            // Log failed advance recording
            $this->auditService->logFailed(
                'record_advance',
                'Advance',
                'Failed to record advance',
                $e->getMessage()
            );

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
            $advanceId = $advance->id;
            $totalAmount = $advance->amount_per_person * $advance->senders()->count();
            $advance->delete();

            // Log advance deletion
            $this->auditService->logSuccess(
                'delete_advance',
                'Advance',
                "Advance of {$totalAmount} deleted from group '{$group->name}'",
                $advanceId,
                $group->id
            );

            return redirect()->back()->with('success', 'Advance deleted successfully');
        } catch (\Exception $e) {
            // Log failed advance deletion
            $this->auditService->logFailed(
                'delete_advance',
                'Advance',
                'Failed to delete advance',
                $e->getMessage()
            );

            return redirect()->back()->with('error', 'Failed to delete advance: ' . $e->getMessage());
        }
    }
}
