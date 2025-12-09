@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">🔧 Super Admin Panel</h1>
        <p class="text-gray-600 mt-2">Manage user plans and group subscriptions</p>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
            <p class="text-green-700">✓ {{ session('success') }}</p>
        </div>
    @endif

    <!-- Users List -->
    <div class="space-y-6">
        @foreach($users as $user)
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <!-- User Header -->
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $user['name'] }}</h2>
                            <p class="text-gray-600">{{ $user['email'] }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Current User Plan Badge -->
                            <span class="px-4 py-2 rounded-full text-sm font-bold {{ $user['plan'] === 'lifetime' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $user['plan'] === 'lifetime' ? '⭐ Lifetime' : 'Free' }}
                            </span>
                            
                            <!-- Update User Plan -->
                            <form action="{{ route('admin.users.update-plan', $user['id']) }}" method="POST" class="inline">
                                @csrf
                                <select name="plan" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Change Plan...</option>
                                    <option value="free" {{ $user['plan'] === 'free' ? 'selected' : '' }}>Set to Free</option>
                                    <option value="lifetime" {{ $user['plan'] === 'lifetime' ? 'selected' : '' }}>Set to Lifetime</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- User's Groups -->
                <div class="p-6">
                    @if($user['groups']->count() > 0)
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Groups Created ({{ $user['groups']->count() }})</h3>
                        <div class="space-y-4">
                            @foreach($user['groups'] as $group)
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h4 class="font-semibold text-gray-900">{{ $group['name'] }}</h4>
                                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $group['plan'] === 'trip_pass' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $group['plan'] === 'trip_pass' ? '🎫 Trip Pass' : 'Free' }}
                                                </span>
                                                <span class="text-sm text-gray-600">{{ $group['members_count'] }} members</span>
                                            </div>
                                            
                                            <div class="text-sm text-gray-600 space-y-1">
                                                <p>OCR Scans Used: <span class="font-semibold">{{ $group['ocr_scans_used'] }}/{{ $group['plan'] === 'trip_pass' ? '∞' : '5' }}</span></p>
                                                @if($group['plan_expires_at'])
                                                    <p>Expires: <span class="font-semibold">{{ \Carbon\Carbon::parse($group['plan_expires_at'])->format('M d, Y') }}</span></p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <!-- Update Group Plan -->
                                            <form action="{{ route('admin.groups.update-plan', $group['id']) }}" method="POST" class="inline">
                                                @csrf
                                                <div class="flex gap-2">
                                                    <select name="plan" class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500">
                                                        <option value="">Change Plan...</option>
                                                        <option value="free">Set to Free</option>
                                                        <option value="trip_pass">Activate Trip Pass</option>
                                                    </select>
                                                    <input type="number" name="days_valid" placeholder="Days" min="1" max="3650" value="365" class="w-20 px-2 py-2 border border-gray-300 rounded text-sm">
                                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                                        Update
                                                    </button>
                                                </div>
                                            </form>

                                            <!-- Reset OCR Counter -->
                                            @if($group['ocr_scans_used'] > 0)
                                                <form action="{{ route('admin.groups.reset-ocr', $group['id']) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-2 bg-orange-600 text-white rounded text-sm hover:bg-orange-700" onclick="return confirm('Reset OCR counter to 0?')">
                                                        Reset OCR
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 italic">No groups created yet</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Quick Actions Guide -->
    <div class="mt-8 bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
        <h3 class="font-bold text-blue-900 mb-2">📝 Quick Guide</h3>
        <ul class="text-sm text-blue-800 space-y-1">
            <li><strong>User Plan:</strong> Set to "Lifetime" for unlimited access across all groups</li>
            <li><strong>Group Plan:</strong> Set to "Trip Pass" for unlimited OCR in that specific group</li>
            <li><strong>Days Valid:</strong> How long the Trip Pass should last (default: 365 days)</li>
            <li><strong>Reset OCR:</strong> Reset the OCR counter back to 0 for free users</li>
        </ul>
    </div>
</div>
@endsection
