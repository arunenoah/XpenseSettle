@extends('layouts.app')

@section('title', 'Manage Members - ' . $group->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <!-- Navigation Menu -->
    <div class="bg-white rounded-xl shadow-md p-2 flex gap-2 overflow-x-auto mb-6">
        <!-- Dashboard -->
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all" title="Dashboard">
            <span class="text-xl">📊</span>
            <span class="hidden sm:inline">Dashboard</span>
        </a>
        <!-- Members -->
        <a href="{{ route('groups.members', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-bold whitespace-nowrap shadow-lg hover:from-purple-700 hover:to-pink-700 transition-all" title="Members">
            <span class="text-xl">👥</span>
            <span class="hidden sm:inline">Members</span>
        </a>
        <!-- Payment History -->
        <a href="{{ route('groups.payments.history', $group) }}" class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all" title="Payment History">
            <span class="text-xl">📜</span>
            <span class="hidden sm:inline">History</span>
        </a>
        <!-- Expenses Modal -->
        <a href="#" onclick="showExpensesModal(); return false;" class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all" title="All Expenses">
            <span class="text-xl">📋</span>
            <span class="hidden sm:inline">Expenses</span>
        </a>
        <!-- Settings (Admin Only) -->
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}" class="flex items-center gap-2 px-3 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all" title="Settings">
                <span class="text-xl">⚙️</span>
                <span class="hidden sm:inline">Settings</span>
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

    <!-- Mobile Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 flex flex-col gap-3 sm:hidden z-40">
        <a href="{{ route('groups.expenses.create', $group) }}" class="inline-flex justify-center items-center w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-full hover:from-green-600 hover:to-emerald-600 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Expense">
            <span class="text-2xl">💸</span>
        </a>
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}" class="inline-flex justify-center items-center w-12 h-12 bg-gradient-to-r from-orange-400 to-pink-400 text-white rounded-full hover:from-orange-500 hover:to-pink-500 transition-all transform hover:scale-110 font-bold shadow-lg text-sm" title="Edit Group">
                <span class="text-lg">✏️</span>
            </a>
        @endif
        <a href="{{ route('groups.dashboard', $group) }}" class="inline-flex justify-center items-center w-12 h-12 bg-gradient-to-r from-blue-400 to-cyan-400 text-white rounded-full hover:from-blue-500 hover:to-cyan-500 transition-all transform hover:scale-110 font-bold shadow-lg text-sm" title="Dashboard">
            <span class="text-lg">📊</span>
        </a>
    </div>

    <!-- Expenses Modal -->
    <div id="expensesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto" onclick="closeExpensesModal(event)">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 my-8" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-black text-gray-900">📜 Expenses</h3>
                <button onclick="closeExpensesModal()" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
            </div>

            @if($group->expenses->count() > 0)
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($group->expenses as $expense)
                        <a href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}" class="block p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 truncate">{{ $expense->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">💰 ${{ number_format($expense->amount, 2) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">📅 {{ $expense->date->format('M d, Y') }} • 👤 {{ $expense->payer->name }}</p>
                                </div>
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold flex-shrink-0 {{ $expense->status === 'fully_paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-600">No expenses yet. <a href="{{ route('groups.expenses.create', $group) }}" class="text-blue-600 hover:text-blue-700 font-bold">Create one →</a></p>
                </div>
            @endif
        </div>
    </div>

    <script>
    function showExpensesModal() {
        document.getElementById('expensesModal').classList.remove('hidden');
        document.getElementById('expensesModal').classList.add('flex');
    }

    function closeExpensesModal(event) {
        if (!event || event.target.id === 'expensesModal') {
            document.getElementById('expensesModal').classList.add('hidden');
            document.getElementById('expensesModal').classList.remove('flex');
        }
    }
    </script>

</div>
@endsection
