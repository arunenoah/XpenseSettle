<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Services\PlanService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Show admin dashboard with all users and their plans
     */
    public function index()
    {
        $users = User::with(['createdGroups' => function($query) {
            $query->withCount('members');
        }])->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'plan_expires_at' => $user->plan_expires_at,
                'groups' => $user->createdGroups->map(function($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'plan' => $group->plan,
                        'plan_expires_at' => $group->plan_expires_at,
                        'ocr_scans_used' => $group->ocr_scans_used,
                        'members_count' => $group->members_count,
                    ];
                })
            ];
        });

        return view('admin.index', compact('users'));
    }

    /**
     * Update user plan
     */
    public function updateUserPlan(Request $request, User $user)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,lifetime',
        ]);

        if ($validated['plan'] === 'lifetime') {
            $this->planService->activateLifetimePlan($user);
            $message = "{$user->name} upgraded to Lifetime plan!";
        } else {
            $user->update(['plan' => 'free', 'plan_expires_at' => null]);
            $message = "{$user->name} set to Free plan";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Update group plan
     */
    public function updateGroupPlan(Request $request, Group $group)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,trip_pass',
            'days_valid' => 'nullable|integer|min:1|max:3650',
        ]);

        if ($validated['plan'] === 'trip_pass') {
            $days = $validated['days_valid'] ?? 365;
            $this->planService->activateTripPass($group, $days);
            $message = "Trip Pass activated for '{$group->name}' (valid for {$days} days)";
        } else {
            $group->update([
                'plan' => 'free',
                'plan_expires_at' => null,
                'ocr_scans_used' => 0,
            ]);
            $message = "'{$group->name}' set to Free plan";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Reset OCR counter for a group
     */
    public function resetOCRCounter(Group $group)
    {
        $group->update(['ocr_scans_used' => 0]);
        return redirect()->back()->with('success', "OCR counter reset for '{$group->name}'");
    }
}
