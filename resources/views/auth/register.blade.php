<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SettleX</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">⚖️ SettleX</h1>
                <p class="text-gray-600">Track shared expenses with ease</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Create Your Account</h2>

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li class="text-sm text-red-700">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-500' : '' }}"
                            placeholder="John Doe"
                            required
                            autofocus
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('email') ? 'border-red-500' : '' }}"
                            placeholder="you@example.com"
                            required
                        />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('password') ? 'border-red-500' : '' }}"
                            placeholder="••••••••"
                            required
                        />
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">At least 8 characters with uppercase, lowercase, and number</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('password_confirmation') ? 'border-red-500' : '' }}"
                            placeholder="••••••••"
                            required
                        />
                        @error('password_confirmation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 6-Digit PIN -->
                    <div>
                        <label for="pin" class="block text-sm font-semibold text-gray-700 mb-2">6-Digit PIN</label>
                        <input
                            type="password"
                            id="pin"
                            name="pin"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base text-center tracking-widest text-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('pin') ? 'border-red-500' : '' }}"
                            placeholder="••••••"
                            required
                            inputmode="numeric"
                        />
                        @error('pin')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Create a 6-digit PIN for quick login</p>
                    </div>

                    <!-- Confirm PIN -->
                    <div>
                        <label for="pin_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm PIN</label>
                        <input
                            type="password"
                            id="pin_confirmation"
                            name="pin_confirmation"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg text-base text-center tracking-widest text-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('pin_confirmation') ? 'border-red-500' : '' }}"
                            placeholder="••••••"
                            required
                            inputmode="numeric"
                        />
                        @error('pin_confirmation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="terms"
                            name="terms"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            required
                        />
                        <label for="terms" class="ml-2 block text-sm text-gray-700">
                            I agree to the
                            <a href="#" class="text-blue-600 hover:text-blue-700">Terms of Service</a>
                            and
                            <a href="#" class="text-blue-600 hover:text-blue-700">Privacy Policy</a>
                        </label>
                    </div>

                    <!-- Sign Up Button -->
                    <button
                        type="submit"
                        class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                    >
                        Create Account
                    </button>
                </form>

                <!-- Divider -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">or</span>
                        </div>
                    </div>
                </div>

                <!-- Sign In Link -->
                <p class="mt-6 text-center text-sm text-gray-600">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">
                        Sign in
                    </a>
                </p>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-500">
                By signing up, you agree to our Terms of Service and Privacy Policy
            </p>
        </div>
    </div>
</body>
</html>
