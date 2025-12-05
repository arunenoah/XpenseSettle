@extends('layouts.app')

@section('title', $group->name)

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="space-y-6 sm:space-y-8">
        <!-- Navigation Menu -->
        <div class="bg-white rounded-xl shadow-md p-2 flex gap-2 overflow-x-auto">
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-bold whitespace-nowrap text-base shadow-lg">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('groups.members', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">👥</span>
            <span>Members</span>
        </a>
        <a href="{{ route('groups.expenses.create', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">💸</span>
            <span>Add Expense</span>
        </a>
        <a href="{{ route('groups.payments.history', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">📋</span>
            <span>Payment History</span>
        </a>
        <a href="#" onclick="showExpensesModal(); return false;" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">📜</span>
            <span>Expenses</span>
        </a>
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
                <span class="text-xl">✏️</span>
                <span>Settings</span>
            </a>
        @endif
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-br from-purple-100 via-pink-100 to-blue-100 rounded-3xl shadow-xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-5xl sm:text-6xl">{{ $group->icon ?? '🎉' }}</span>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $group->name }}</h1>
                        @if($group->description)
                            <p class="mt-1 text-gray-700 font-medium">{{ $group->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <span class="px-3 py-1 bg-white rounded-full text-sm font-bold text-purple-600 shadow-sm">
                        💰 {{ $group->currency }}
                    </span>
                    <span class="px-3 py-1 bg-white rounded-full text-sm font-bold text-blue-600 shadow-sm">
                        👥 {{ $group->members->count() }} squad members
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <a href="{{ route('groups.expenses.create', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all transform hover:scale-105 font-bold shadow-lg">
                    <span class="text-xl mr-2">💸</span>
                    Add Expense
                </a>
                @if($group->isAdmin(auth()->user()))
                    <a href="{{ route('groups.edit', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-orange-400 to-pink-400 text-white rounded-xl hover:from-orange-500 hover:to-pink-500 transition-all transform hover:scale-105 font-bold shadow-lg">
                        <span class="text-xl mr-2">✏️</span>
                        Edit
                    </a>
                    <a href="{{ route('groups.members', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-blue-400 to-cyan-400 text-white rounded-xl hover:from-blue-500 hover:to-cyan-500 transition-all transform hover:scale-105 font-bold shadow-lg">
                        <span class="text-xl mr-2">👥</span>
                        Members
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- User's Balance Card -->
    <div class="bg-gradient-to-br from-yellow-50 via-orange-50 to-red-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-xl sm:text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">💰</span>
            <span>Your Money Situation</span>
        </h2>
        @php
            $totalOwed = collect($settlement)->filter(fn($s) => $s['net_amount'] > 0)->sum('amount');
            $totalOwe = collect($settlement)->filter(fn($s) => $s['net_amount'] < 0)->sum('amount');
            $netBalance = $totalOwe - $totalOwed;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-md border-2 border-red-200">
                <p class="text-sm font-bold text-red-700 flex items-center gap-2 mb-2">
                    <span class="text-xl">😬</span>
                    <span>You Owe</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black text-red-600">${{ number_format($totalOwed, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-md border-2 border-green-200">
                <p class="text-sm font-bold text-green-700 flex items-center gap-2 mb-2">
                    <span class="text-xl">🤑</span>
                    <span>They Owe You</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black text-green-600">${{ number_format($totalOwe, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-md border-2 {{ $netBalance >= 0 ? 'border-green-200' : 'border-orange-200' }}">
                <p class="text-sm font-bold {{ $netBalance >= 0 ? 'text-green-700' : 'text-orange-700' }} flex items-center gap-2 mb-2">
                    <span class="text-xl">{{ $netBalance >= 0 ? '✅' : '⚠️' }}</span>
                    <span>Net Balance</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black {{ $netBalance >= 0 ? 'text-green-600' : 'text-orange-600' }}">
                    {{ $netBalance >= 0 ? '+' : '' }}${{ number_format($netBalance, 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Analytics -->
    <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg p-4">
        <h2 class="text-lg font-black text-gray-900 mb-3 flex items-center gap-2">
            <span class="text-2xl">📊</span>
            <span>Group Analytics</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-gray-600 mb-1">💰 Total</p>
                <p class="text-xl font-black text-gray-900">${{ number_format($totalOwed + $totalOwe, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-red-600 mb-1">😬 You Owe</p>
                <p class="text-xl font-black text-red-600">${{ number_format($totalOwed, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-green-600 mb-1">🤑 They Owe</p>
                <p class="text-xl font-black text-green-600">${{ number_format($totalOwe, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-blue-600 mb-1">📝 Expenses</p>
                <p class="text-xl font-black text-blue-600">{{ $expenses->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Settlement Breakdown (Net per Person) -->
    @if(count($settlement) > 0)
        <div class="bg-gradient-to-br from-violet-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-6">
            <h3 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-3xl">⚖️</span>
                <span>Settlement Summary</span>
            </h3>
            <div class="space-y-3">
                @foreach($settlement as $item)
                    @php
                        $isOwed = $item['net_amount'] > 0;
                        $bgColor = $isOwed ? 'from-red-50 to-orange-50' : 'from-green-50 to-emerald-50';
                        $borderColor = $isOwed ? 'border-orange-200' : 'border-green-200';
                        $textColor = $isOwed ? 'text-orange-600' : 'text-green-600';
                        $label = $isOwed ? 'You Owe' : 'They Owe You';
                        $emoji = $isOwed ? '😬' : '🤑';
                    @endphp
                    <div class="p-4 bg-gradient-to-r {{ $bgColor }} border {{ $borderColor }} rounded-lg">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <span class="text-2xl">{{ $emoji }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900">{{ $item['user']->name }}</p>
                                    <p class="text-sm {{ $textColor }} font-semibold">{{ $label }}</p>
                                </div>
                            </div>
                            <p class="font-black text-2xl {{ $textColor }} flex-shrink-0">${{ number_format($item['amount'], 2) }}</p>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            @if($item['status'] === 'pending')
                                <span class="inline-block px-3 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">Pending</span>
                                <!-- DEBUG: net_amount={{ $item['net_amount'] }}, payment_ids_count={{ count($item['payment_ids'] ?? []) }} -->
                                @if($item['net_amount'] > 0 && count($item['payment_ids'] ?? []) > 0)
                                    <button onclick="openGroupPaymentModal({{ $item['payment_ids'][0] }}, '{{ $item['user']->name }}', {{ $item['amount'] }}, '{{ addslashes($item['user']->name) }}')"
                                            class="px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all font-bold text-xs">
                                        ✓ Mark Paid
                                    </button>
                                @endif
                            @elseif($item['status'] === 'paid')
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Paid</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-gradient-to-br from-green-100 via-emerald-100 to-teal-100 rounded-3xl shadow-xl p-8">
            <div class="text-center">
                <div class="text-7xl mb-4">🎉</div>
                <h3 class="text-3xl font-black text-green-900 mb-2">All Settled!</h3>
                <p class="text-lg font-semibold text-green-700">Everyone's square! No one owes anyone 🙌</p>
                <p class="text-sm text-green-600 mt-2">Time to add more expenses! 😄</p>
            </div>
        </div>
    @endif

    <!-- Advances Section -->
    <div class="bg-gradient-to-br from-blue-50 via-cyan-50 to-teal-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">💰</span>
            <span>Advances</span>
        </h2>

        <!-- Add Advance Form -->
        <div class="bg-white rounded-xl p-6 shadow-md border-2 border-blue-200 mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">➕ Add Advance</h3>
            <form action="{{ route('groups.advances.store', $group) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Sent To -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sent To</label>
                        <select name="sent_to_user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Select Person --</option>
                            @foreach($group->members as $member)
                                @if($member->id !== auth()->id())
                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount Per Person -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Per Person</label>
                        <input type="number" name="amount_per_person" step="0.01" min="0" placeholder="100" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description (Optional)</label>
                        <input type="text" name="description" placeholder="e.g., Travel advance" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Senders -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Who Sent This Advance?</label>
                    <div class="space-y-2">
                        @foreach($group->members as $member)
                            <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                <input type="checkbox" name="senders[]" value="{{ $member->id }}" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-gray-700">{{ $member->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-white font-bold rounded-lg hover:from-blue-600 hover:to-cyan-600 transition-all">
                    ➕ Record Advance
                </button>
            </form>
        </div>

        <!-- Advances List -->
        @php
            $advances = \App\Models\Advance::where('group_id', $group->id)->with(['senders', 'sentTo'])->latest()->get();
        @endphp

        @if($advances->count() > 0)
            <div class="space-y-3">
                @foreach($advances as $advance)
                    <div class="p-4 bg-white border-2 border-blue-200 rounded-lg">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900">
                                    {{ $advance->senders->pluck('name')->join(', ') }} → {{ $advance->sentTo->name }}
                                </p>
                                @if($advance->description)
                                    <p class="text-sm text-gray-600">{{ $advance->description }}</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-1">{{ $advance->date->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-black text-lg text-blue-600">${{ number_format($advance->amount_per_person * $advance->senders()->count(), 2) }}</p>
                                <p class="text-xs text-gray-600">({{ $group->currency }}{{ number_format($advance->amount_per_person, 2) }} each)</p>
                            </div>
                        </div>

                        <!-- Delete Button -->
                        <div class="mt-3 flex justify-end">
                            <form action="{{ route('groups.advances.destroy', ['group' => $group, 'advance' => $advance]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this advance record?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded hover:bg-red-200 transition-all">
                                    🗑️ Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 bg-white rounded-xl border-2 border-dashed border-blue-200">
                <p class="text-gray-600 font-medium">No advances recorded yet</p>
                <p class="text-sm text-gray-500 mt-1">Use the form above to add an advance</p>
            </div>
        @endif
    </div>

    <!-- Squad Members -->
    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">👥</span>
            <span>Squad Members</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($balances as $balance)
                <div class="bg-white rounded-xl p-5 shadow-md border-2 {{ $balance['net_balance'] >= 0 ? 'border-green-300' : 'border-red-300' }} hover:shadow-xl transition-all transform hover:scale-105">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <span class="text-xl font-black text-white">{{ strtoupper(substr($balance['user']->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-bold text-gray-900 truncate">{{ $balance['user']->name }}</p>
                            @if($balance['user']->id === auth()->id())
                                <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">That's You! 👋</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600">💸 Paid</span>
                            <span class="text-sm font-bold text-gray-900">${{ number_format($balance['total_paid'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600">💰 Share</span>
                            <span class="text-sm font-bold text-gray-900">${{ number_format($balance['total_owed'], 2) }}</span>
                        </div>
                        @if(isset($memberAdvances[$balance['user']->id]) && $memberAdvances[$balance['user']->id] > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-semibold text-blue-600">🚀 Advanced</span>
                                <span class="text-sm font-bold text-blue-600">${{ number_format($memberAdvances[$balance['user']->id], 2) }}</span>
                            </div>
                        @endif
                        <div class="pt-2 border-t-2 border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold {{ $balance['net_balance'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $balance['net_balance'] >= 0 ? '🤑 Gets Back' : '😬 Owes' }}
                                </span>
                                <span class="text-xl font-black {{ $balance['net_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $balance['net_balance'] >= 0 ? '+' : '' }}${{ number_format(abs($balance['net_balance']), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6 p-4 bg-white rounded-xl border-2 border-indigo-200 text-center">
            <p class="text-sm font-semibold text-gray-700">
                <span class="text-lg">💡</span> 
                <span class="text-green-600">Green</span> = Gets money back • 
                <span class="text-red-600">Red</span> = Needs to pay
            </p>
        </div>
    </div>

    <!-- Payment History -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border-b-2 border-gray-200 px-6 py-4">
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span class="text-3xl">📋</span>
                <span>Payment History</span>
            </h2>
        </div>

        @if($payments->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-50 to-purple-50 border-b-2 border-gray-200">
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Expense</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Person</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Amount</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-gray-50 transition-all">
                                <!-- Expense Title -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <p class="font-semibold text-gray-900">{{ $payment->split->expense->title }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $payment->id }}</p>
                                    </div>
                                </td>

                                <!-- Person -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($payment->split->user->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $payment->split->user->name }}</span>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-900">${{ number_format($payment->split->share_amount, 2) }}</p>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-6 py-4">
                                    @if($payment->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full">⏳ Pending</span>
                                    @elseif($payment->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">✓ Paid</span>
                                    @elseif($payment->status === 'approved')
                                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">✓ Approved</span>
                                    @elseif($payment->status === 'rejected')
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">✗ Rejected</span>
                                    @endif
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        @if($payment->paid_date)
                                            <p class="text-sm text-gray-900 font-medium">{{ $payment->paid_date->format('M d, Y') }}</p>
                                        @else
                                            <p class="text-sm text-gray-500 italic">—</p>
                                        @endif
                                        <p class="text-xs text-gray-500">{{ $payment->created_at->diffForHumans() }}</p>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    @if($payment->attachments->count() > 0)
                                        <button onclick="toggleAttachments({{ $payment->id }})" class="inline-flex items-center gap-1 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all text-xs font-bold">
                                            📎 {{ $payment->attachments->count() }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">No attachments</span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Attachments Row -->
                            @if($payment->attachments->count() > 0)
                                <tr id="attachments-{{ $payment->id }}" class="hidden bg-blue-50">
                                    <td colspan="6" class="px-6 py-4">
                                        <div class="space-y-2">
                                            <h4 class="font-bold text-gray-900 mb-3">📎 Attachments:</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($payment->attachments as $attachment)
                                                    <div class="bg-white rounded-lg p-3 border-2 border-blue-200">
                                                        <div class="flex items-start gap-2">
                                                            @if(str_contains($attachment->mime_type, 'image'))
                                                                <img src="{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}" alt="Attachment" class="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity" onclick="openImageModal('{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}', '{{ addslashes($attachment->file_name) }}')">
                                                            @else
                                                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                                                    <span class="text-2xl">📄</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex-1">
                                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $attachment->file_name }}</p>
                                                                <p class="text-xs text-gray-500">{{ $attachment->file_size_kb }} KB</p>
                                                                <p class="text-xs text-gray-500">{{ $attachment->created_at->format('M d, Y') }}</p>
                                                                <a href="{{ route('attachments.download', ['attachment' => $attachment->id]) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 font-bold mt-1 inline-block">
                                                                    Download →
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-center">
                <a href="{{ route('groups.payments.history', $group) }}" class="text-sm font-bold text-blue-600 hover:text-blue-700">
                    View All Payments →
                </a>
            </div>
        @else
            <div class="px-6 py-8 text-center">
                <div class="text-4xl mb-3">📭</div>
                <p class="text-gray-600 font-semibold">No payments yet</p>
            </div>
        @endif
    </div>

    <!-- Advances Section -->
    @php
        $groupAdvances = \App\Models\Advance::where('group_id', $group->id)
            ->with('senders', 'sentTo')
            ->latest()
            ->limit(5)
            ->get();
    @endphp

    @if($groupAdvances->count() > 0)
        <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg p-6">
            <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-3xl">💰</span>
                <span>Recent Advances</span>
            </h2>

            <div class="space-y-3">
                @foreach($groupAdvances as $advance)
                    <div class="bg-white rounded-xl p-4 border-2 border-cyan-200 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-blue-400 flex items-center justify-center flex-shrink-0 mt-1">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr($advance->sentTo->name, 0, 1)) }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-gray-900">Advanced to {{ $advance->sentTo->name }}</p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Paid by: <span class="font-semibold">{{ $advance->senders->pluck('name')->join(', ') }}</span>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $advance->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-xl font-bold text-cyan-600">${{ number_format($advance->amount_per_person, 2) }}</p>
                                <p class="text-xs text-gray-500 mt-1">per person</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('groups.payments.history', $group) }}" class="text-sm font-bold text-cyan-600 hover:text-cyan-700">
                    View All Advances →
                </a>
            </div>
        </div>
    @endif

    <!-- Recent Expenses -->
    <div class="bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">📜</span>
            <span>Recent Activity</span>
        </h2>
        @if(count($expenses) > 0)
            <div class="space-y-3 overflow-y-auto max-h-screen pr-2" style="max-height: 600px;">
                @foreach($expenses as $expense)
                    <div class="bg-white p-5 rounded-xl border-2 border-orange-200 hover:shadow-lg hover:border-orange-400 transition-all transform hover:scale-102">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-black text-lg text-gray-900 truncate flex items-center gap-2">
                                    <span>💰</span>
                                    {{ $expense->title }}
                                </h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-sm font-semibold text-gray-700">
                                        👤 {{ $expense->payer->name }} paid
                                    </span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-xs font-semibold text-gray-500">
                                        👥 {{ $expense->splits->count() }} people
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-gray-500 mt-1">
                                    📅 {{ $expense->date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-2xl font-black text-orange-600">${{ number_format($expense->amount, 2) }}</p>
                                <span class="inline-block mt-1 px-3 py-1 bg-gradient-to-r from-blue-100 to-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                    {{ ucfirst(str_replace('_', ' ', $expense->split_type)) }} split
                                </span>
                            </div>
                        </div>
                        @if($expense->description)
                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-600 italic">💬 "{{ $expense->description }}"</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl p-8 text-center border-2 border-dashed border-orange-300">
                <div class="text-6xl mb-4">🤷</div>
                <p class="text-lg font-bold text-gray-700 mb-2">No expenses yet!</p>
                <p class="text-sm text-gray-600">Click "Add Expense" to get started 🚀</p>
            </div>
        @endif
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

        @if($expenses->count() > 0)
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($expenses as $expense)
                    <a href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}" class="block p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 truncate">{{ $expense->title }}</h4>
                                <p class="text-sm text-gray-600 mt-1">💰 {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($expense->amount, 2) }}</p>
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

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50 p-4" onclick="closeImageModal(event)">
    <div class="relative max-w-3xl max-h-96 bg-white rounded-2xl overflow-hidden" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute top-3 right-3 bg-white rounded-full p-2 hover:bg-gray-100 z-10 text-gray-700 font-bold">✕</button>
        <img id="modalImage" src="" alt="Image" class="w-full h-full object-contain">
        <p id="modalImageName" class="absolute bottom-3 left-3 bg-black bg-opacity-50 text-white text-sm px-3 py-1 rounded truncate max-w-xs"></p>
    </div>
</div>

<!-- Payment Modal -->
<div id="groupPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeGroupPaymentModal(event)">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <h3 class="text-2xl font-black text-gray-900 mb-4">Mark Payment as Paid</h3>

        <form id="groupPaymentForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                <p class="text-sm text-gray-600">Paying to:</p>
                <p class="text-lg font-black text-gray-900" id="groupPayeeName"></p>
                <p class="text-3xl font-black text-green-600 mt-2" id="groupPaymentAmount"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Payment Notes (Optional)</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="e.g., Paid via UPI, Reference: TXN123"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Upload Receipt (Optional)</label>
                <input type="file" name="receipt" accept="image/png,image/jpeg" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">📸 PNG or JPEG, max 5MB (auto-compressed to 50KB)</p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeGroupPaymentModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all font-bold">
                    ✓ Mark as Paid
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openGroupPaymentModal(paymentId, payeeName, amount, expenseTitle) {
    document.getElementById('groupPayeeName').textContent = payeeName;
    document.getElementById('groupPaymentAmount').textContent = '$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('groupPaymentForm').action = '/payments/' + paymentId + '/mark-paid';
    document.getElementById('groupPaymentModal').classList.remove('hidden');
    document.getElementById('groupPaymentModal').classList.add('flex');
}

function closeGroupPaymentModal(event) {
    if (!event || event.target.id === 'groupPaymentModal') {
        document.getElementById('groupPaymentModal').classList.add('hidden');
        document.getElementById('groupPaymentModal').classList.remove('flex');
    }
}

function toggleAttachments(paymentId) {
    const row = document.getElementById('attachments-' + paymentId);
    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
    } else {
        row.classList.add('hidden');
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

function openImageModal(imageUrl, imageName) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('modalImageName').textContent = imageName;
    document.getElementById('imageModal').classList.remove('hidden');
    document.getElementById('imageModal').classList.add('flex');
}

function closeImageModal(event) {
    if (!event || event.target.id === 'imageModal') {
        document.getElementById('imageModal').classList.add('hidden');
        document.getElementById('imageModal').classList.remove('flex');
    }
}
</script>
@endsection
