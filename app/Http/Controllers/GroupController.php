<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupMemberService;
use App\Services\GroupService;
use App\Services\NotificationService;
use App\Services\PlanService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    private GroupService $groupService;
    private NotificationService $notificationService;
    private PlanService $planService;

    public function __construct(GroupService $groupService, NotificationService $notificationService, PlanService $planService)
    {
        $this->groupService = $groupService;
        $this->notificationService = $notificationService;
        $this->planService = $planService;
    }

    /**
     * Display list of all groups for the user.
     */
    public function index()
    {
        $groups = auth()->user()->groups()
            ->withoutTrashed()
            ->with('creator', 'members')
            ->latest()
            ->paginate(12);

        return view('groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        return view('groups.create');
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'currency' => 'nullable|string|in:USD,EUR,GBP,INR,AUD,CAD',
        ]);

        try {
            $group = $this->groupService->createGroup(auth()->user(), $validated);

            return redirect()
                ->route('groups.show', $group)
                ->with('success', 'Group created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create group: ' . $e->getMessage());
        }
    }

    /**
     * Display group dashboard with member balances and expenses.
     */
    public function show(Group $group)
    {
        // Check if user is member
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'Unauthorized to view this group');
        }

        // Get member balances
        $balances = $this->groupService->getGroupBalance($group);

        // Get recent expenses with relationships
        $expenses = $group->expenses()
            ->with('payer', 'splits.user', 'splits.contact', 'comments')
            ->latest()
            ->paginate(10);

        // Get current user's balance info
        $userBalance = $balances[auth()->id()] ?? [
            'user' => auth()->user(),
            'total_owed' => 0,
            'total_paid' => 0,
            'net_balance' => 0,
        ];

        // Get pending payments for current user
        $pendingPayments = auth()->user()
            ->expenseSplits()
            ->whereHas('expense', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->whereDoesntHave('payment', function ($q) {
                $q->where('status', 'paid');
            })
            ->with('expense', 'payment')
            ->get();

        return view('groups.show', compact(
            'group',
            'balances',
            'expenses',
            'userBalance',
            'pendingPayments'
        ));
    }

    /**
     * Show the form for editing a group.
     */
    public function edit(Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only admins can edit this group');
        }

        return view('groups.edit', compact('group'));
    }

    /**
     * Update the group.
     */
    public function update(Request $request, Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only admins can edit this group');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'currency' => 'nullable|string|in:USD,EUR,GBP,INR,AUD,CAD',
        ]);

        try {
            $group = $this->groupService->updateGroup($group, $validated);

            return redirect()
                ->route('groups.show', $group)
                ->with('success', 'Group updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update group: ' . $e->getMessage());
        }
    }

    /**
     * Delete the group.
     */
    public function destroy(Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only admins can delete this group');
        }

        try {
            $groupName = $group->name;
            $this->groupService->deleteGroup($group);

            return redirect()
                ->route('groups.index')
                ->with('success', "Group '{$groupName}' deleted successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete group: ' . $e->getMessage());
        }
    }

    /**
     * Show group members management page.
     */
    public function members(Group $group)
    {
        // Check authorization - allow all members to view, only admins can edit
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Get all users except current members
        $currentMemberIds = $group->members()->pluck('user_id');
        $availableUsers = \App\Models\User::whereNotIn('id', $currentMemberIds)->get();

        // Get all members (users + contacts) with relationships loaded
        $allMembers = $group->allMembers()->get();

        return view('groups.members', compact('group', 'availableUsers', 'allMembers'));
    }

    /**
     * Add a member to the group.
     */
    public function addMember(Request $request, Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            return redirect()->back()->with('error', 'Only admins can add members');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if user is already a member
        if ($group->hasMember(User::find($validated['user_id']))) {
            return redirect()->back()->with('error', 'User is already a member');
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $validated['user_id'],
            'role' => 'member',
        ]);

        $user = User::find($validated['user_id']);

        // Send notification to the newly added user
        $this->notificationService->notifyUserAddedToGroup($user, $group, auth()->user());

        return redirect()->back()->with('success', $user->name . ' has been added to the group! 🎉');
    }

    /**
     * Add a contact to the group (for bill splitting only, no group access).
     */
    public function addContact(Request $request, Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            return redirect()->back()->with('error', 'Only admins can add contacts');
        }

        $validated = $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_family_count' => 'nullable|integer|min:0|max:20',
        ]);

        try {
            $service = new GroupMemberService();
            $service->addContactMember(
                $group,
                $validated['contact_name'],
                $validated['contact_email'],
                $validated['contact_phone'],
                'member',
                $validated['contact_family_count'] ?? 0
            );

            return redirect()->back()->with('success', $validated['contact_name'] . ' has been added for bill splitting! ✨');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to add contact: ' . $e->getMessage());
        }
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Group $group, GroupMember $member)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only admins can remove members');
        }

        // Prevent removing yourself
        if ($member->user_id === auth()->id()) {
            return back()->with('error', 'You cannot remove yourself from the group');
        }

        try {
            $memberName = $member->user->name;
            $this->groupService->removeMember($group, $member->user);

            return back()->with('success', "{$memberName} has been removed from the group");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove member: ' . $e->getMessage());
        }
    }

    /**
     * Update member family count.
     */
    public function updateFamilyCount(Request $request, Group $group, $memberId)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only admins can update family count'], 403);
            }
            abort(403, 'Only admins can update family count');
        }

        $validated = $request->validate([
            'family_count' => 'required|integer|min:1|max:20',
        ]);

        try {
            $group->members()->updateExistingPivot($memberId, [
                'family_count' => $validated['family_count']
            ]);

            // Clear any cached relationships
            $group->load('members');

            // Get the updated value from database to confirm
            $updatedMember = $group->members()->where('users.id', $memberId)->first();
            $actualFamilyCount = $updatedMember->pivot->family_count ?? $validated['family_count'];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Family count updated successfully!',
                    'family_count' => $actualFamilyCount
                ]);
            }

            return back()->with('success', 'Family count updated successfully!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update family count: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update family count: ' . $e->getMessage());
        }
    }

    /**
     * Update contact family count.
     */
    public function updateContactFamilyCount(Request $request, Group $group, Contact $contact)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Only admins can update family count'], 403);
            }
            abort(403, 'Only admins can update family count');
        }

        // Verify contact belongs to this group
        if ($contact->group_id !== $group->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Contact does not belong to this group'], 404);
            }
            abort(404, 'Contact not found');
        }

        $validated = $request->validate([
            'family_count' => 'required|integer|min:0|max:20',
        ]);

        try {
            $contact->update([
                'family_count' => $validated['family_count']
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Family count updated successfully!',
                    'family_count' => $contact->family_count
                ]);
            }

            return back()->with('success', 'Family count updated successfully!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update family count: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to update family count: ' . $e->getMessage());
        }
    }

    /**
     * Update member role.
     */
    public function updateMemberRole(Request $request, Group $group, GroupMember $member)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Only admins can update member roles');
        }

        $validated = $request->validate([
            'role' => 'required|in:member,admin',
        ]);

        try {
            $this->groupService->updateMemberRole(
                $group,
                $member->user,
                $validated['role']
            );

            return back()->with('success', 'Member role updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update member role: ' . $e->getMessage());
        }
    }

    /**
     * Get group dashboard data (for AJAX if needed).
     */
    public function getDashboardData(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $balances = $this->groupService->getGroupBalance($group);

        return response()->json([
            'group' => $group,
            'balances' => $balances,
            'total_expenses' => $group->expenses()->count(),
            'total_members' => $group->members()->count(),
        ]);
    }

    /**
     * Leave the group (for non-admin members)
     */
    public function leaveGroup(Group $group)
    {
        $user = auth()->user();

        if ($group->isAdmin($user)) {
            return redirect()->back()->with('error', 'Admins cannot leave the group. Transfer admin rights first or delete the group.');
        }

        // Check if user has pending payments
        $hasPendingPayments = \App\Models\Payment::whereHas('split.expense', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })
        ->whereHas('split', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('status', 'pending')
        ->exists();

        if ($hasPendingPayments) {
            return redirect()->back()->with('error', 'You have pending payments. Please settle them before leaving.');
        }

        GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('groups.index')->with('success', 'You have left the group');
    }

    /**
     * Increment OCR scan counter for free users
     */
    public function incrementOCR(Group $group)
    {
        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Increment OCR scan counter
        $this->planService->incrementOCRScan($group);

        return response()->json(['success' => true, 'remaining' => $this->planService->getRemainingOCRScans($group)]);
    }

    /**
     * Activate Trip Pass for a group (TESTING ONLY - Replace with payment integration)
     */
    public function activateTripPass(Group $group)
    {
        // Check if user is admin of the group
        if (!$group->isAdmin(auth()->user())) {
            return redirect()->back()->with('error', 'Only group admins can upgrade plans');
        }

        // Activate trip pass
        $this->planService->activateTripPass($group);

        return redirect()->back()->with('success', 'Trip Pass activated! You now have unlimited OCR scans for this group.');
    }

    /**
     * Activate Lifetime plan for user (TESTING ONLY - Replace with payment integration)
     */
    public function activateLifetime()
    {
        // Activate lifetime plan
        $this->planService->activateLifetimePlan(auth()->user());

        return redirect()->back()->with('success', 'Lifetime plan activated! You now have unlimited access to all features across all groups.');
    }
}
