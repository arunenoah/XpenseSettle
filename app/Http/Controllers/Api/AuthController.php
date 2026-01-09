<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle mobile app login request with PIN only.
     */
    public function login(Request $request)
    {
        // Rate limiting: 5 attempts per minute
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }
        
        $validated = $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        // Get all users and check PIN hash against each
        $users = User::all();
        $authenticatedUser = null;

        foreach ($users as $user) {
            if ($user->pin && Hash::check($validated['pin'], $user->pin)) {
                $authenticatedUser = $user;
                break;
            }
        }

        // Check if user exists and PIN matches
        if ($authenticatedUser) {
            // Create Sanctum token for API access
            $sanctumToken = $authenticatedUser->createToken('mobile')->plainTextToken;

            // Log successful login
            $this->auditService->logLogin($authenticatedUser);

            // Clear rate limiter on successful login
            RateLimiter::clear($key);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $sanctumToken,
                'user' => [
                    'id' => $authenticatedUser->id,
                    'name' => $authenticatedUser->name,
                    'email' => $authenticatedUser->email,
                ]
            ]);
        }

        // Increment rate limiter on failed login
        RateLimiter::hit($key, $decayMinutes * 60);

        // Log failed login attempt
        $this->auditService->logFailed(
            'login',
            'User',
            'Failed login attempt',
            'Invalid PIN provided'
        );

        return response()->json([
            'success' => false,
            'message' => 'Invalid PIN. Please try again.'
        ], 401);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
