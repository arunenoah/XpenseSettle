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

            <!-- Add New Member / Contact -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="flex border-b border-gray-200">
                    <button onclick="switchTab('user-tab', 'contact-tab')" id="user-tab-btn" class="flex-1 px-4 py-4 text-center font-semibold text-gray-900 border-b-2 border-purple-600 transition-all">
                        👤 Add User Member
                    </button>
                    <button onclick="switchTab('contact-tab', 'user-tab')" id="contact-tab-btn" class="flex-1 px-4 py-4 text-center font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700 transition-all">
                        ✨ Add Contact
                    </button>
                </div>

                <div class="p-6">
                    <!-- Add User Member Tab -->
                    <div id="user-tab" class="block">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2 flex items-center gap-2">
                            <span>👤</span> <span>Add existing user with full group access</span>
                        </h3>
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
                                Add User
                            </button>
                        </form>

                        @if($availableUsers->isEmpty())
                            <p class="mt-4 text-sm text-gray-600 text-center bg-blue-50 border border-blue-200 rounded-lg p-4">
                                🎉 All users are already members of this group!
                            </p>
                        @endif
                    </div>

                    <!-- Add Contact Tab -->
                    <div id="contact-tab" class="hidden">
                        <h3 class="text-sm font-semibold text-gray-600 mb-2 flex items-center gap-2">
                            <span>✨</span> <span>Add contact for bill splitting only (no group access)</span>
                        </h3>
                        <form action="{{ route('groups.contacts.add', $group) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                                <input type="text" name="contact_name" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="e.g., Mom, Dad, John" required>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (Optional)</label>
                                    <input type="email" name="contact_email" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="e.g., mom@example.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone (Optional)</label>
                                    <input type="tel" name="contact_phone" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="e.g., +1 234 567 8900">
                                </div>
                            </div>
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition-all transform hover:scale-105 font-bold shadow-lg">
                                Add Contact
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Current Members -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">👥</span>
            <span>Current Members ({{ $allMembers->count() }})</span>
        </h2>

        <div class="space-y-3">
            @forelse($allMembers as $groupMember)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-10 h-10 rounded-full {{ $groupMember->isActiveUser() ? 'bg-gradient-to-br from-blue-400 to-blue-600' : 'bg-gradient-to-br from-cyan-400 to-blue-500' }} flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($groupMember->getMemberName(), 0, 1)) }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 flex items-center gap-2">
                                {{ $groupMember->getMemberName() }}
                                @if($groupMember->isContact())
                                    <span class="px-2 py-1 bg-cyan-100 text-cyan-800 text-xs font-semibold rounded">
                                        ✨ Contact
                                    </span>
                                @endif
                                @if($groupMember->role === 'admin')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded">
                                        👑 Admin
                                    </span>
                                @endif
                                @if($groupMember->user_id === auth()->id())
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                        You
                                    </span>
                                @endif
                            </p>
                            @if($groupMember->isActiveUser())
                                <p class="text-sm text-gray-600">{{ $groupMember->user->email }}</p>
                            @else
                                @if($groupMember->contact->email)
                                    <p class="text-sm text-gray-600">{{ $groupMember->contact->email }}</p>
                                @endif
                                @if($groupMember->contact->phone)
                                    <p class="text-sm text-gray-600">{{ $groupMember->contact->phone }}</p>
                                @endif
                            @endif

                            @if($groupMember->isActiveUser())
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-gray-500 flex items-center gap-1">
                                        <span class="text-base">👨‍👩‍👧‍👦</span>
                                        <span class="hidden sm:inline">Family Count:</span>
                                    </span>
                                    @if($group->isAdmin(auth()->user()))
                                        <form action="{{ route('groups.members.update-family-count', [$group, $groupMember->user_id]) }}"
                                              method="POST"
                                              class="inline-flex items-center gap-1"
                                              onsubmit="updateFamilyCount(event, {{ $groupMember->user_id }})">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number"
                                                   name="family_count"
                                                   id="family_count_{{ $groupMember->user_id }}"
                                                   value="{{ $groupMember->family_count ?? 0 }}"
                                                   min="1"
                                                   max="20"
                                                   class="w-12 sm:w-16 px-1 sm:px-2 py-1 text-xs sm:text-sm border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 text-center font-semibold">
                                            <button type="submit"
                                                    id="update_btn_{{ $groupMember->user_id }}"
                                                    class="w-7 h-7 sm:w-auto sm:h-auto sm:px-2 sm:py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-full sm:rounded flex items-center justify-center transition-all active:scale-95"
                                                    title="Update family count">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span class="hidden sm:inline text-xs font-semibold">Update</span>
                                            </button>
                                            <span id="status_{{ $groupMember->user_id }}" class="text-base sm:text-xs font-semibold"></span>
                                        </form>
                                    @else
                                        <span class="text-sm font-semibold text-purple-600 px-2 py-1 bg-purple-50 rounded">{{ $groupMember->family_count ?? 0 }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($group->isAdmin(auth()->user()))
                        @if($groupMember->isActiveUser() && $groupMember->user_id !== auth()->id())
                            <form action="{{ route('groups.members.remove', [$group, $groupMember->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove {{ $groupMember->getMemberName() }} from this group?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 sm:w-auto sm:h-auto sm:px-3 sm:py-1 bg-red-500 hover:bg-red-600 text-white rounded-full sm:rounded flex items-center justify-center transition-all active:scale-95" title="Remove member">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span class="hidden sm:inline text-sm font-semibold">Remove</span>
                                </button>
                            </form>
                        @elseif($groupMember->isContact())
                            <form action="{{ route('groups.members.remove', [$group, $groupMember->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove {{ $groupMember->getMemberName() }} from this group?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 sm:w-auto sm:h-auto sm:px-3 sm:py-1 bg-red-500 hover:bg-red-600 text-white rounded-full sm:rounded flex items-center justify-center transition-all active:scale-95" title="Remove contact">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span class="hidden sm:inline text-sm font-semibold">Remove</span>
                                </button>
                            </form>
                        @endif
                    @elseif($groupMember->isActiveUser() && $groupMember->user_id === auth()->id() && $groupMember->role !== 'admin')
                        <form action="{{ route('groups.members.leave', $group) }}" method="POST" onsubmit="return confirm('Are you sure you want to leave this group?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-8 h-8 sm:w-auto sm:h-auto sm:px-3 sm:py-1 bg-gray-500 hover:bg-gray-600 text-white rounded-full sm:rounded flex items-center justify-center transition-all active:scale-95" title="Leave group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span class="hidden sm:inline text-sm font-semibold">Leave</span>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-center py-8">
                    <p class="text-gray-600">No members yet. Add a user or contact to get started!</p>
                </div>
            @endforelse
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
    function switchTab(activeTabId, inactiveTabId) {
        // Show active tab, hide inactive tab
        document.getElementById(activeTabId).classList.remove('hidden');
        document.getElementById(inactiveTabId).classList.add('hidden');

        // Update button styles
        if (activeTabId === 'user-tab') {
            document.getElementById('user-tab-btn').classList.add('text-gray-900', 'border-b-purple-600');
            document.getElementById('user-tab-btn').classList.remove('text-gray-500', 'border-b-transparent');
            document.getElementById('contact-tab-btn').classList.remove('text-gray-900', 'border-b-purple-600');
            document.getElementById('contact-tab-btn').classList.add('text-gray-500', 'border-b-transparent');
        } else {
            document.getElementById('contact-tab-btn').classList.add('text-gray-900', 'border-b-purple-600');
            document.getElementById('contact-tab-btn').classList.remove('text-gray-500', 'border-b-transparent');
            document.getElementById('user-tab-btn').classList.remove('text-gray-900', 'border-b-purple-600');
            document.getElementById('user-tab-btn').classList.add('text-gray-500', 'border-b-transparent');
        }
    }

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
        const originalButtonHTML = button.innerHTML;
        
        // Disable button and show loading spinner
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
        status.textContent = '';
        status.className = 'text-base sm:text-xs font-semibold';
        
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
                
                // Update the input value to match what was saved
                input.value = data.family_count;
                
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
            // Re-enable button and restore original content
            button.disabled = false;
            button.innerHTML = originalButtonHTML;
        }
    }
    </script>

</div>
@endsection
