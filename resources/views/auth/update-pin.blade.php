@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-8">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 text-center">Update PIN</h2>
            <p class="text-gray-600 text-center mt-2">Change your 6-digit PIN for mobile login</p>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="text-red-600 text-xl mr-3">⚠️</span>
                    <div>
                        <h3 class="text-red-800 font-semibold mb-2">Unable to Update PIN</h3>
                        <ul class="text-red-700 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('auth.update-pin') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Current PIN -->
            <div>
                <label for="current_pin" class="block text-sm font-medium text-gray-700 mb-2">
                    Current PIN
                </label>
                <input
                    type="password"
                    id="current_pin"
                    name="current_pin"
                    placeholder="Enter your current 6-digit PIN"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center text-lg tracking-widest @error('current_pin') border-red-500 @enderror"
                    value="{{ old('current_pin') }}"
                    required
                >
                @error('current_pin')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200"></div>

            <!-- New PIN -->
            <div>
                <label for="new_pin" class="block text-sm font-medium text-gray-700 mb-2">
                    New PIN
                </label>
                <input
                    type="password"
                    id="new_pin"
                    name="new_pin"
                    placeholder="Enter your new 6-digit PIN"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center text-lg tracking-widest @error('new_pin') border-red-500 @enderror"
                    value="{{ old('new_pin') }}"
                    required
                >
                @error('new_pin')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm New PIN -->
            <div>
                <label for="new_pin_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirm New PIN
                </label>
                <input
                    type="password"
                    id="new_pin_confirmation"
                    name="new_pin_confirmation"
                    placeholder="Re-enter your new PIN"
                    inputmode="numeric"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-center text-lg tracking-widest @error('new_pin_confirmation') border-red-500 @enderror"
                    value="{{ old('new_pin_confirmation') }}"
                    required
                >
                @error('new_pin_confirmation')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Message -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 text-sm flex items-start">
                    <span class="text-blue-600 mr-2">ℹ️</span>
                    <span>Your PIN must be exactly 6 digits and different from your current PIN.</span>
                </p>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
            >
                <span class="mr-2">🔐</span>
                Update PIN
            </button>

            <!-- Cancel Link -->
            <div class="text-center">
                <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                    Cancel
                </a>
            </div>
        </form>

        <!-- Footer Info -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-gray-600 text-xs text-center">
                Remember your new PIN. You'll need it to log in on mobile devices.
            </p>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('nonce', '') }}">
    // Restrict input to numeric only
    document.querySelectorAll('input[type="password"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    });
</script>
@endsection
