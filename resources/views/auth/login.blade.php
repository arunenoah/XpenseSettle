<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ExpenseSettle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <h1 class="text-4xl font-bold text-blue-600 mb-2">💰 ExpenseSettle</h1>
                <p class="text-gray-600">Track shared expenses with ease</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- PIN -->
                    <div>
                        <label for="pin" class="block text-sm font-semibold text-gray-700 mb-2 text-center">Enter Your 6-Digit PIN</label>
                        <input
                            type="password"
                            id="pin"
                            name="pin"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-6 border-2 border-gray-300 rounded-lg text-center tracking-widest text-3xl font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $errors->has('pin') ? 'border-red-500' : '' }}"
                            placeholder="••••••"
                            required
                            autofocus
                            inputmode="numeric"
                        />
                        @error('pin')
                            <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="remember"
                            name="remember"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                    </div>

                    <!-- Enter Button -->
                    <button
                        type="submit"
                        class="w-full py-4 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-bold text-lg"
                    >
                        Enter
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-500">
                By signing in, you agree to our Terms of Service and Privacy Policy
            </p>
        </div>
    </div>
</body>
</html>
