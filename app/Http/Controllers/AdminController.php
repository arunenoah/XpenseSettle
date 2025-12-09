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
     * Show admin PIN verification page
     */
    public function showPinVerification()
    {
        // Check if already verified in this session
        if (session('admin_verified')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.verify-pin');
    }

    /**
     * Verify admin PIN
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'admin_pin' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        // Check if admin PIN matches using Hash::check
        if (!$user->admin_pin || !\Hash::check($request->admin_pin, $user->admin_pin)) {
            return back()->withErrors(['admin_pin' => 'Invalid admin PIN. Please try again.']);
        }

        // Set session flag for admin access
        session(['admin_verified' => true, 'admin_verified_at' => now()]);

        return redirect()->route('admin.dashboard')->with('success', 'Admin access granted!');
    }

    /**
     * Logout from admin panel
     */
    public function logout()
    {
        session()->forget(['admin_verified', 'admin_verified_at']);
        return redirect()->route('dashboard')->with('success', 'Logged out from admin panel');
    }

    /**
     * Show admin dashboard with all users and their plans
     */
    public function index()
    {
        // Check if admin session is still valid (30 minutes)
        if (session('admin_verified_at') && now()->diffInMinutes(session('admin_verified_at')) > 30) {
            session()->forget(['admin_verified', 'admin_verified_at']);
            return redirect()->route('admin.verify')->with('error', 'Admin session expired. Please verify again.');
        }

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
