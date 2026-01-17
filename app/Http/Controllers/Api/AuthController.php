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
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'status' => 429
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
                'status' => 200,
                'data' => [
                    'token' => $sanctumToken,
                    'user' => [
                        'id' => $authenticatedUser->id,
                        'name' => $authenticatedUser->name,
                        'email' => $authenticatedUser->email,
                    ]
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
            'message' => 'Invalid PIN. Please try again.',
            'status' => 401
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
            'message' => 'Logged out successfully',
            'status' => 200
        ]);
    }

    /**
     * Update user PIN via API
     * Requires authentication
     *
     * @param Request $request
     * @return array
     */
    public function updatePin(Request $request)
    {
        $user = $request->user();

        try {
            // Validate current PIN and new PIN
            $validated = $request->validate([
                'current_pin' => [
                    'required',
                    'string',
                    'digits:6',
                    function ($attribute, $value, $fail) use ($user) {
                        // Verify current PIN matches using Hash::check
                        if (!Hash::check($value, $user->pin)) {
                            $fail('The current PIN is incorrect.');
                        }
                    },
                ],
                'new_pin' => [
                    'required',
                    'string',
                    'digits:6',
                    'different:current_pin',
                    function ($attribute, $value, $fail) {
                        // Ensure new PIN is unique by checking all other users
                        $users = User::where('id', '!=', auth()->id())->get();
                        foreach ($users as $otherUser) {
                            if ($otherUser->pin && Hash::check($value, $otherUser->pin)) {
                                $fail('This PIN is already taken by another user.');
                                break;
                            }
                        }
                    },
                ],
                'new_pin_confirmation' => 'required|string|digits:6|same:new_pin',
            ]);

            // Update the user's PIN (auto-hashed by model cast)
            $user->update([
                'pin' => $validated['new_pin'],
            ]);

            // Log the PIN change
            $this->auditService->logSuccess(
                'update_pin',
                'User',
                "{$user->name} updated their PIN",
                $user->id
            );

            return [
                'success' => true,
                'message' => 'PIN updated successfully',
                'status' => 200,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                ]
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return [
                'success' => false,
                'message' => 'Validation failed',
                'status' => 422,
                'errors' => $e->errors(),
            ];
        } catch (\Exception $e) {
            // Handle other errors
            return [
                'success' => false,
                'message' => 'Failed to update PIN: ' . $e->getMessage(),
                'status' => 500,
            ];
        }
    }
}
