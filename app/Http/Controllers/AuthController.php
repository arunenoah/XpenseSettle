<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle login request with PIN only.
     */
    public function login(Request $request)
    {
        // Rate limiting: 5 attempts per minute
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'pin' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }
        
        $validated = $request->validate([
            'pin' => 'required|string|digits:6',
        ]);

        // Get all users and check PIN hash against each
        $users = User::all();
        $authenticatedUser = null;

        foreach ($users as $user) {
            if (Hash::check($validated['pin'], $user->pin)) {
                $authenticatedUser = $user;
                break;
            }
        }

        // Check if user exists and PIN matches
        if ($authenticatedUser) {
            Auth::login($authenticatedUser, $request->boolean('remember'));
            $request->session()->regenerate();

            // Create Sanctum token for API access (mobile/Capacitor app)
            $sanctumToken = $authenticatedUser->createToken('mobile')->plainTextToken;
            session(['sanctum_token' => $sanctumToken]);

            // Clear rate limiter on successful login
            RateLimiter::clear($key);

            return redirect()->intended(route('dashboard'));
        }

        // Increment rate limiter on failed login
        RateLimiter::hit($key, $decayMinutes * 60);

        return back()->withErrors([
            'pin' => 'Invalid PIN. Please try again.',
        ]);
    }

    /**
     * Show the registration form.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/'
            ],
            'pin' => [
                'required',
                'string',
                'digits:6',
                'confirmed',
                function ($attribute, $value, $fail) {
                    // Check if PIN is already in use by checking all users
                    $users = User::all();
                    foreach ($users as $user) {
                        if (Hash::check($value, $user->pin)) {
                            $fail('This PIN is already taken. Please choose a different one.');
                            break;
                        }
                    }
                },
            ],
        ], [
            'password.regex' => 'Password must contain at least 8 characters, one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#)',
            'pin.digits' => 'PIN must be exactly 6 digits.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'pin' => $validated['pin'], // Auto-hashed by model cast
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'))->with('success', 'Registration successful! Welcome to ExpenseSettle.');
    }

    /**
     * Show the PIN update form.
     */
    public function showUpdatePin()
    {
        return view('auth.update-pin');
    }

    /**
     * Handle PIN update request.
     */
    public function updatePin(Request $request)
    {
        $user = auth()->user();

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
                    // Ensure new PIN is unique by checking all users
                    $users = User::where('id', '!=', auth()->id())->get();
                    foreach ($users as $otherUser) {
                        if (Hash::check($value, $otherUser->pin)) {
                            $fail('This PIN is already taken by another user.');
                            break;
                        }
                    }
                },
            ],
            'new_pin_confirmation' => 'required|string|digits:6|same:new_pin',
        ], [
            'current_pin.digits' => 'Current PIN must be exactly 6 digits.',
            'current_pin.required' => 'Current PIN is required.',
            'new_pin.digits' => 'New PIN must be exactly 6 digits.',
            'new_pin.required' => 'New PIN is required.',
            'new_pin.different' => 'New PIN must be different from your current PIN.',
            'new_pin_confirmation.same' => 'The PIN confirmation does not match the new PIN.',
            'new_pin_confirmation.required' => 'PIN confirmation is required.',
        ]);

        // Update the user's PIN (auto-hashed by model cast)
        $user->update([
            'pin' => $validated['new_pin'],
        ]);

        return redirect()->route('dashboard')->with('success', 'Your PIN has been updated successfully!');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        // Delete all tokens for the authenticated user
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('login'))->with('success', 'You have been logged out.');
    }
}
