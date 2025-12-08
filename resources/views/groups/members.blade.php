@extends('layouts.app')

@section('title', 'Manage Members - ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <!-- Header Section -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 border-b border-gray-200">
        <div class="max-w-7xl mx-auto">
            <!-- Group Title -->
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-5xl">{{ $group->icon ?? '👥' }}</span>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $group->name }}</h1>
                        @if($group->description)
                            <p class="text-gray-600 mt-1">{{ $group->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Desktop Action Buttons -->
                <div class="hidden sm:flex gap-2 flex-shrink-0">
                    <a href="{{ route('groups.expenses.create', $group) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-sm">
                        Add Expense
                    </a>
                    @if($group->isAdmin(auth()->user()))
                        <a href="{{ route('groups.edit', $group) }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all font-semibold text-sm">
                            Settings
                        </a>
                    @endif
                </div>
            </div>

            <!-- Member Avatars and Info -->
            <div class="flex items-center gap-4">
                <span class="text-xs font-semibold text-gray-600 uppercase">{{ $group->members->count() }} Members</span>
                <div class="flex items-center gap-2">
                    @foreach($group->members->take(5) as $member)
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 border-2 border-white shadow-sm" title="{{ $member->name }}">
                            <span class="text-xs font-bold text-blue-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                        </div>
                    @endforeach
                    @if($group->members->count() > 5)
                        <span class="text-xs font-semibold text-gray-600 ml-1">+{{ $group->members->count() - 5 }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <x-group-tabs :group="$group" active="members" />

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Add New Member -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">👥</span>
            <span>Current Members ({{ $group->members->count() }})</span>
        </h2>

        <div class="space-y-3">
            @foreach($group->members as $member)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 flex items-center gap-2">
                                {{ $member->name }}
                                @if($member->pivot->role === 'admin')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">
                                        👑 Admin
                                    </span>
                                @endif
                                @if($member->id === auth()->id())
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                        You
                                    </span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-600">{{ $member->email }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-xs text-gray-500">👨‍👩‍👧‍👦 Family Count:</span>
                                @if($group->isAdmin(auth()->user()))
                                    <form action="{{ route('groups.members.update-family-count', [$group, $member->id]) }}" 
                                          method="POST" 
                                          class="inline-flex items-center gap-1"
                                          onsubmit="updateFamilyCount(event, {{ $member->id }})">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" 
                                               name="family_count" 
                                               id="family_count_{{ $member->id }}"
                                               value="{{ $member->pivot->family_count ?? 1 }}" 
                                               min="1" 
                                               max="20" 
                                               class="w-16 px-2 py-1 text-xs border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                        <button type="submit" 
                                                id="update_btn_{{ $member->id }}"
                                                class="px-2 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded text-xs font-semibold transition-colors">
                                            Update
                                        </button>
                                        <span id="status_{{ $member->id }}" class="text-xs font-semibold"></span>
                                    </form>
                                @else
                                    <span class="text-sm font-semibold text-purple-600">{{ $member->pivot->family_count ?? 1 }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($group->isAdmin(auth()->user()) && $member->id !== auth()->id())
                        <form action="{{ route('groups.members.remove', [$group, $member->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove {{ $member->name }} from this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 rounded text-sm font-semibold transition-colors">
                                Remove
                            </button>
                        </form>
                    @elseif($member->id === auth()->id() && $member->pivot->role !== 'admin')
                        <form action="{{ route('groups.members.leave', $group) }}" method="POST" onsubmit="return confirm('Are you sure you want to leave this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded text-sm font-semibold transition-colors">
                                Leave
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
            </div>
        </div>
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

    async function updateFamilyCount(event, memberId) {
        event.preventDefault();
        
        const form = event.target;
        const input = document.getElementById(`family_count_${memberId}`);
        const button = document.getElementById(`update_btn_${memberId}`);
        const status = document.getElementById(`status_${memberId}`);
        const originalValue = input.value;
        
        // Disable button and show loading
        button.disabled = true;
        button.textContent = '...';
        status.textContent = '';
        status.className = 'text-xs font-semibold';
        
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Success - show green checkmark
                status.textContent = '✓';
                status.className = 'text-xs font-semibold text-green-600';
                
                // Flash the input green
                input.classList.add('border-green-500', 'bg-green-50');
                setTimeout(() => {
                    input.classList.remove('border-green-500', 'bg-green-50');
                    status.textContent = '';
                }, 2000);
            } else {
                // Error - show red X and revert value
                status.textContent = '✗';
                status.className = 'text-xs font-semibold text-red-600';
                input.value = originalValue;
                
                // Flash the input red
                input.classList.add('border-red-500', 'bg-red-50');
                setTimeout(() => {
                    input.classList.remove('border-red-500', 'bg-red-50');
                    status.textContent = '';
                }, 2000);
            }
        } catch (error) {
            // Network error - show red X and revert
            status.textContent = '✗';
            status.className = 'text-xs font-semibold text-red-600';
            input.value = originalValue;
            
            input.classList.add('border-red-500', 'bg-red-50');
            setTimeout(() => {
                input.classList.remove('border-red-500', 'bg-red-50');
                status.textContent = '';
            }, 2000);
        } finally {
            // Re-enable button
            button.disabled = false;
            button.textContent = 'Update';
        }
    }
    </script>

</div>
@endsection
