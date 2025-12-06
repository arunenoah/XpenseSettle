<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceTokenController extends Controller
{
    /**
     * Register or update device token for current user
     *
     * Called when the mobile app starts - stores the FCM token
     * so we can send push notifications to this device
     *
     * POST /api/device-tokens
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string|min:50', // FCM tokens are long strings
                'device_name' => 'nullable|string|max:255',
                'device_type' => 'required|in:android,ios,web',
                'app_version' => 'nullable|string|max:20',
            ]);

            $user = $request->user();

            // Check if this token already exists for this user
            $existing = DeviceToken::where('user_id', $user->id)
                ->where('token', $validated['token'])
                ->first();

            if ($existing) {
                // Update existing token
                $existing->update([
                    'device_name' => $validated['device_name'],
                    'app_version' => $validated['app_version'],
                    'active' => true,
                    'last_used_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Device token updated',
                    'device_token' => $existing,
                ], 200);
            }

            // Create new device token
            $deviceToken = DeviceToken::create([
                'user_id' => $user->id,
                'token' => $validated['token'],
                'device_name' => $validated['device_name'] ?? 'Mobile Device',
                'device_type' => $validated['device_type'],
                'app_version' => $validated['app_version'],
                'active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'message' => 'Device token registered',
                'device_token' => $deviceToken,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to register device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove/deactivate device token when user logs out or uninstalls
     *
     * DELETE /api/device-tokens/{token}
     */
    public function remove(Request $request)
    {
        try {
            $validated = $request->validate([
                'token' => 'required|string',
            ]);

            $user = $request->user();

            $deviceToken = DeviceToken::where('user_id', $user->id)
                ->where('token', $validated['token'])
                ->first();

            if (!$deviceToken) {
                return response()->json([
                    'message' => 'Device token not found',
                ], 404);
            }

            $deviceToken->deactivate();

            return response()->json([
                'message' => 'Device token removed',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all device tokens for current user
     *
     * GET /api/device-tokens
     */
    public function list(Request $request)
    {
        $user = $request->user();

        $tokens = DeviceToken::where('user_id', $user->id)
            ->where('active', true)
            ->orderBy('last_used_at', 'desc')
            ->get();

        return response()->json([
            'count' => $tokens->count(),
            'device_tokens' => $tokens,
        ], 200);
    }
}
