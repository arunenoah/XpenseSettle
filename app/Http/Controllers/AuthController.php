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

        // Find user by PIN
        $user = User::where('pin', $validated['pin'])->first();

        // Check if user exists
        if ($user) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            
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
            'pin' => 'required|string|digits:6|confirmed|unique:users',
        ], [
            'password.regex' => 'Password must contain at least 8 characters, one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&#)',
            'pin.digits' => 'PIN must be exactly 6 digits.',
            'pin.unique' => 'This PIN is already taken. Please choose a different one.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'pin' => $validated['pin'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard'))->with('success', 'Registration successful! Welcome to ExpenseSettle.');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('home'))->with('success', 'You have been logged out.');
    }
}
