@extends('layouts.app')

@section('title', 'Manage Members - ' . $group->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <!-- Navigation Menu -->
    <div class="bg-white rounded-xl shadow-md p-2 flex gap-2 overflow-x-auto mb-6">
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('groups.members', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-bold whitespace-nowrap text-base shadow-lg">
            <span class="text-xl">👥</span>
            <span>Members</span>
        </a>
        <a href="{{ route('groups.expenses.create', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">💸</span>
            <span>Add Expense</span>
        </a>
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
                <span class="text-xl">✏️</span>
                <span>Settings</span>
            </a>
        @endif
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-br from-purple-100 via-pink-100 to-blue-100 rounded-2xl shadow-lg p-6 mb-6">
        <div class="flex items-center gap-3 mb-2">
            <span class="text-4xl">{{ $group->icon ?? '👥' }}</span>
            <h1 class="text-3xl font-black text-gray-900">{{ $group->name }}</h1>
        </div>
        <p class="text-gray-700 font-medium">Manage group members</p>
    </div>

    <!-- Add New Member -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">➕</span>
            <span>Add New Member</span>
        </h2>
        
        <form action="{{ route('groups.members.add', $group) }}" method="POST" class="flex gap-3">
            @csrf
            <div class="flex-1">
                <select name="user_id" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all" required>
                    <option value="">Select a friend to add...</option>
                    @foreach($availableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105 font-bold shadow-lg">
                Add Member
            </button>
        </form>

        @if($availableUsers->isEmpty())
            <p class="mt-3 text-sm text-gray-600 text-center">
                🎉 All users are already members of this group!
            </p>
        @endif
    </div>

    <!-- Current Members -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">👥</span>
            <span>Current Members ({{ $group->members->count() }})</span>
        </h2>

        <div class="space-y-3">
            @foreach($group->members as $member)
                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl border-2 border-gray-200 hover:border-purple-300 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center">
                            <span class="text-xl font-black text-white">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 flex items-center gap-2">
                                {{ $member->name }}
                                @if($member->pivot->role === 'admin')
                                    <span class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-orange-400 text-white text-xs font-bold rounded-full">
                                        👑 Admin
                                    </span>
                                @endif
                                @if($member->id === auth()->id())
                                    <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                        You
                                    </span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">{{ $member->email }}</p>
                        </div>
                    </div>

                    @if($group->isAdmin(auth()->user()) && $member->id !== auth()->id())
                        <form action="{{ route('groups.members.remove', [$group, $member->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove {{ $member->name }} from this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-lg hover:from-red-600 hover:to-pink-600 transition-all font-bold text-sm">
                                Remove
                            </button>
                        </form>
                    @elseif($member->id === auth()->id() && $member->pivot->role !== 'admin')
                        <form action="{{ route('groups.members.leave', $group) }}" method="POST" onsubmit="return confirm('Are you sure you want to leave this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all font-bold text-sm">
                                Leave Group
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
