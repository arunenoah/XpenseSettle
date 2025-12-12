@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full">
        <!-- Admin Access Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-600 rounded-full mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🔐 Admin Access</h1>
                <p class="text-gray-600">Enter your admin PIN to continue</p>
                <p class="text-sm text-gray-700 mt-2">Logged in as: <strong>{{ auth()->user()->name }}</strong></p>
            </div>

            <!-- Error Message -->
            @if($errors->has('admin_pin'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <p class="text-red-700">{{ $errors->first('admin_pin') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded">
                    <p class="text-orange-700">{{ session('error') }}</p>
                </div>
            @endif

            <!-- PIN Form -->
            <form action="{{ route('admin.verify.submit') }}" method="POST" id="admin-pin-form">
                @csrf
                
                <div class="mb-6">
                    <label for="admin_pin" class="block text-sm font-semibold text-gray-700 mb-2">
                        Admin PIN (6 digits)
                    </label>
                    <input 
                        type="password" 
                        name="admin_pin" 
                        id="admin_pin" 
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        class="w-full px-4 py-4 text-center text-2xl font-bold tracking-widest border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-4 focus:ring-purple-500 focus:border-purple-500 @error('admin_pin') border-red-500 @enderror"
                        placeholder="••••••"
                        required
                        autofocus
                    >
                    <p class="text-xs text-gray-500 mt-2 text-center">This is different from your regular login PIN</p>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-4 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-bold text-lg shadow-md"
                >
                    🔓 Unlock Admin Panel
                </button>
            </form>

            <!-- Back Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    ← Back to Dashboard
                </a>
            </div>

            <!-- Security Notice -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800">Security Notice</p>
                            <p class="text-xs text-yellow-700 mt-1">Admin session expires after 30 minutes of inactivity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Text -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an admin PIN? Contact system administrator.
            </p>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('nonce', '') }}">
// Auto-submit when 6 digits are entered
document.getElementById('admin_pin').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        // Small delay for better UX
        setTimeout(() => {
            document.getElementById('admin-pin-form').submit();
        }, 300);
    }
});

// Only allow numbers
document.getElementById('admin_pin').addEventListener('keypress', function(e) {
    if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
    }
});
</script>
@endsection
