@extends('layouts.app')

@section('title', $group->name)

@section('content')
<!-- Breadcrumb Navigation -->
<div class="px-4 sm:px-6 lg:px-8 py-3 bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto flex items-center gap-2 text-sm">
        <a href="{{ route('groups.index') }}" class="text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
            <span>👥</span>
            <span>All Groups</span>
        </a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700 font-medium">{{ $group->name }}</span>
    </div>
</div>

<!-- Header Section - Optimized & Compact -->
<div class="px-4 sm:px-6 lg:px-8 py-3 sm:py-4 border-b border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto">
            <!-- Compact Header Row -->
            <div class="flex items-center justify-between gap-3 sm:gap-4">
                <!-- Left: Icon & Title & Members (Inline) -->
                <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                    <span class="text-3xl flex-shrink-0">{{ $group->icon ?? '👥' }}</span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 truncate">{{ $group->name }}</h1>
                                @if($group->description)
                                    <p class="text-xs sm:text-sm text-gray-600 truncate">{{ $group->description }}</p>
                                @endif
                            </div>
                            <!-- Member Avatars (Compact) -->
                            <div class="hidden sm:flex items-center gap-1 flex-shrink-0 pl-2 border-l border-gray-200">
                                <span class="text-xs font-semibold text-gray-600 whitespace-nowrap">{{ $group->members->count() }}M</span>
                                <div class="flex items-center gap-1">
                                    @foreach($group->members->take(3) as $member)
                                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 border border-white shadow-sm" title="{{ $member->name }}">
                                            <span class="text-xs font-bold text-blue-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                        </div>
                                    @endforeach
                                    @if($group->members->count() > 3)
                                        <span class="text-xs font-semibold text-gray-600 ml-0.5">+{{ $group->members->count() - 3 }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Desktop Action Buttons -->
                <div class="hidden sm:flex gap-2 flex-shrink-0">
                    <a href="{{ route('groups.payments.export-pdf', $group) }}" 
                       download
                       class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-all font-semibold text-xs flex items-center gap-1" 
                       title="Export Statement PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>PDF</span>
                    </a>
                    <a href="{{ route('groups.summary', $group) }}" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-xs flex items-center gap-1">
                        <span>📊</span>
                        <span>Summary</span>
                    </a>
                    <a href="{{ route('groups.expenses.create', $group) }}" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-xs whitespace-nowrap">
                        + Expense
                    </a>
                    <button onclick="openAdvanceModal()" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-all font-semibold text-xs flex items-center gap-1 whitespace-nowrap">
                        <span>💰</span>
                        <span>Advance</span>
                    </button>
                    @if($group->isAdmin(auth()->user()))
                        <a href="{{ route('groups.edit', $group) }}" class="px-3 py-1.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all font-semibold text-xs">
                            ⚙️
                        </a>
                    @endif
                </div>
            </div>

            <!-- Mobile Member Info -->
            <div class="flex items-center gap-2 sm:hidden mt-2">
                <span class="text-xs font-semibold text-gray-600">{{ $group->members->count() }} Members:</span>
                <div class="flex items-center gap-1">
                    @foreach($group->members->take(4) as $member)
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 border border-white shadow-sm" title="{{ $member->name }}">
                            <span class="text-xs font-bold text-blue-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                        </div>
                    @endforeach
                    @if($group->members->count() > 4)
                        <span class="text-xs font-semibold text-gray-600 ml-1">+{{ $group->members->count() - 4 }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<x-group-tabs :group="$group" active="dashboard" />

<!-- Main Content -->
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- User's Balance Summary -->
            @php
                $totalOwed = collect($settlement)->filter(fn($s) => $s['net_amount'] > 0)->sum('amount');
                $totalOwe = collect($settlement)->filter(fn($s) => $s['net_amount'] < 0)->sum('amount');
                $netBalance = $totalOwe - $totalOwed;
            @endphp
            <!-- Balance Cards - Optimized for Mobile -->
            <div class="grid grid-cols-3 gap-2 sm:gap-6">
                <!-- You Owe -->
                <div class="bg-white rounded-lg shadow-sm border border-red-200 p-3 sm:p-6">
                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-2">You Owe</h3>
                    <p class="text-lg sm:text-3xl font-bold text-red-600">{{ $group->currency }}{{ number_format($totalOwed, 0) }}</p>
                    <p class="hidden sm:block text-xs text-gray-500 mt-2">Amount owed in this group</p>
                </div>

                <!-- They Owe You -->
                <div class="bg-white rounded-lg shadow-sm border border-green-200 p-3 sm:p-6">
                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-2">They Owe You</h3>
                    <p class="text-lg sm:text-3xl font-bold text-green-600">{{ $group->currency }}{{ number_format($totalOwe, 0) }}</p>
                    <p class="hidden sm:block text-xs text-gray-500 mt-2">Amount owed to you</p>
                </div>

                <!-- Net Balance -->
                <div class="bg-white rounded-lg shadow-sm border {{ $netBalance >= 0 ? 'border-green-200' : 'border-red-200' }} p-3 sm:p-6">
                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-2">Your Balance</h3>
                    <p class="text-lg sm:text-3xl font-bold {{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $netBalance >= 0 ? '+' : '' }}{{ $group->currency }}{{ number_format(abs($netBalance), 0) }}
                    </p>
                    <p class="hidden sm:block text-xs text-gray-500 mt-2">{{ $netBalance >= 0 ? 'You are owed' : 'You owe' }}</p>
                </div>
            </div>

            <!-- Family Cost Widgets -->
            <div class="grid grid-cols-2 gap-2 sm:gap-6">
                <!-- Member Share -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-sm border border-purple-200 p-3 sm:p-6">
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <span class="text-xl sm:text-2xl">💰</span>
                        <h3 class="text-xs sm:text-sm font-semibold text-purple-900">Member Share</h3>
                    </div>
                    <p class="text-lg sm:text-3xl font-bold text-purple-700">{{ $group->currency }}{{ number_format($perMemberShare, 2) }}</p>
                    <p class="text-xs text-purple-600 mt-1 sm:mt-2">
                        <span class="font-semibold">Total: {{ $group->currency }}{{ number_format($totalFamilyCost, 2) }}</span>
                    </p>
                </div>

                <!-- Per Head Cost -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-sm border border-blue-200 p-3 sm:p-6">
                    <div class="flex items-center gap-2 mb-1 sm:mb-2">
                        <span class="text-xl sm:text-2xl">👤</span>
                        <h3 class="text-xs sm:text-sm font-semibold text-blue-900">Per Head Cost</h3>
                    </div>
                    <p class="text-lg sm:text-3xl font-bold text-blue-700">{{ $group->currency }}{{ number_format($perHeadCost, 2) }}</p>
                    <p class="text-xs text-blue-600 mt-1 sm:mt-2">
                        <span class="font-semibold">{{ $totalFamilyCount }} family members</span>
                        <span class="hidden sm:inline"> total</span>
                    </p>
                </div>
            </div>

    <!-- Quick Analytics - HIDDEN -->
    {{--
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
    --}}

    <!-- Squad Members - Collapsible Section -->
    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-6">
        <button type="button" onclick="toggleSection('squadMembers')" class="w-full flex items-center justify-between mb-6 hover:opacity-80 transition-opacity">
            <h2 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span class="text-3xl">👥</span>
                <span>Squad Members</span>
            </h2>
            <span id="squadMembersToggle" class="text-2xl transform transition-transform duration-300">▼</span>
        </button>
        <div id="squadMembers" class="hidden sm:block grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            @foreach($balances as $balance)
                <div class="bg-white rounded-lg p-3 sm:p-5 shadow-md border-2 {{ $balance['net_balance'] >= 0 ? 'border-green-300' : 'border-red-300' }} hover:shadow-lg transition-shadow transform hover:scale-105">
                    <div class="flex items-start gap-2 sm:gap-3 mb-2 sm:mb-3">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <span class="text-lg sm:text-xl font-black text-white">{{ strtoupper(substr($balance['user']->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm font-bold text-gray-900 truncate">{{ $balance['user']->name }}</p>
                            <div class="flex gap-1 mt-1">
                                @if($balance['is_contact'])
                                    <span class="inline-block px-1.5 py-0.5 bg-cyan-100 text-cyan-700 text-xs font-bold rounded-full whitespace-nowrap">✨ Contact</span>
                                @endif
                                @if(!$balance['is_contact'] && $balance['user']->id === auth()->id())
                                    <span class="inline-block px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-full whitespace-nowrap">You 👋</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-gray-600">💸 Paid</span>
                            <span class="font-bold text-gray-900">${{ number_format($balance['total_paid'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-semibold text-gray-600">💰 Share</span>
                            <span class="font-bold text-gray-900">${{ number_format($balance['total_owed'], 2) }}</span>
                        </div>
                        @if(isset($memberAdvances[$balance['user']->id]) && $memberAdvances[$balance['user']->id] > 0)
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-semibold text-blue-600">🚀 Adv</span>
                                <span class="font-bold text-blue-600">${{ number_format($memberAdvances[$balance['user']->id], 2) }}</span>
                            </div>
                        @endif
                        <div class="pt-1 sm:pt-2 border-t border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-xs sm:text-sm font-bold {{ $balance['net_balance'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $balance['net_balance'] >= 0 ? '🤑' : '😬' }}
                                </span>
                                <span class="text-base sm:text-lg font-black {{ $balance['net_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
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

    <!-- Settlement Breakdown (Net per Person) - Collapsible Section -->
    @if(count($settlement) > 0)
        <div class="bg-gradient-to-br from-violet-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-6">
            <button type="button" onclick="toggleSection('settlementSummary')" class="w-full flex items-center justify-between mb-6 hover:opacity-80 transition-opacity">
                <h3 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                    <span class="text-3xl">⚖️</span>
                    <span>Settlement Summary</span>
                </h3>
                <span id="settlementSummaryToggle" class="text-2xl transform transition-transform duration-300">▼</span>
            </button>
            <div id="settlementSummary" class="hidden sm:block space-y-3">
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
                                @if($item['net_amount'] > 0 && isset($item['split_ids']) && count($item['split_ids']) > 0)
                                    <button onclick="openGroupPaymentModal({{ $item['split_ids'][0] }}, '{{ $item['user']->name }}', {{ $item['amount'] }}, '{{ addslashes($item['user']->name) }}')"
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


    <!-- Recent Activity (Expenses, Payments, Advances) -->
    <div class="bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">📜</span>
            <span>Recent Activity</span>
        </h2>
        @php
            // Combine expenses, payments, and advances into one array for chronological display
            $allActivities = [];

            // Add expenses
            foreach ($expenses as $expense) {
                $allActivities[] = [
                    'type' => 'expense',
                    'timestamp' => $expense->created_at,
                    'data' => $expense
                ];
            }

            // Add payments
            foreach ($recentPayments as $payment) {
                $allActivities[] = [
                    'type' => 'payment',
                    'timestamp' => $payment->created_at,
                    'data' => $payment
                ];
            }

            // Add advances
            foreach ($recentAdvances as $advance) {
                $allActivities[] = [
                    'type' => 'advance',
                    'timestamp' => $advance->created_at,
                    'data' => $advance
                ];
            }

            // Sort by timestamp descending (newest first)
            usort($allActivities, function($a, $b) {
                return $b['timestamp']->timestamp <=> $a['timestamp']->timestamp;
            });
        @endphp

        @if(count($allActivities) > 0)
            <div class="space-y-3 overflow-y-auto max-h-screen pr-2" style="max-height: 600px;">
                @foreach($allActivities as $activity)
                    @if($activity['type'] === 'expense')
                        @php $expense = $activity['data']; @endphp
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
                    @elseif($activity['type'] === 'payment')
                        @php $payment = $activity['data']; @endphp
                        <div class="bg-white p-5 rounded-xl border-2 border-green-200 hover:shadow-lg hover:border-green-400 transition-all transform hover:scale-102">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-black text-lg text-gray-900 truncate flex items-center gap-2">
                                        <span>✓</span>
                                        {{ $payment->split->expense->title }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-sm font-semibold text-gray-700">
                                            👤 {{ $payment->split->getMemberName() }} paid
                                        </span>
                                        <span class="text-gray-400">→</span>
                                        <span class="text-sm font-semibold text-gray-700">
                                            {{ $payment->split->expense->payer->name }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-500 mt-1">
                                        📅 {{ $payment->paid_date->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-2xl font-black text-green-600">${{ number_format($payment->split->share_amount, 2) }}</p>
                                    <span class="inline-block mt-1 px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">
                                        💳 Paid
                                    </span>
                                </div>
                            </div>
                        </div>
                    @elseif($activity['type'] === 'advance')
                        @php $advance = $activity['data']; @endphp
                        <div class="bg-white p-5 rounded-xl border-2 border-cyan-200 hover:shadow-lg hover:border-cyan-400 transition-all transform hover:scale-102">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-black text-lg text-gray-900 truncate flex items-center gap-2">
                                        <span>💰</span>
                                        Advance to {{ $advance->sentTo->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="text-sm font-semibold text-gray-700">
                                            Paid by: {{ $advance->senders->pluck('name')->join(', ') }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-500 mt-1">
                                        📅 {{ $advance->date->format('M d, Y') }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-2xl font-black text-cyan-600">${{ number_format($advance->amount_per_person, 2) }}</p>
                                    <span class="inline-block mt-1 px-3 py-1 bg-cyan-100 text-cyan-700 text-xs font-bold rounded-full">
                                        🚀 Advance
                                    </span>
                                </div>
                            </div>
                            @if($advance->description)
                                <div class="pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600 italic">💬 "{{ $advance->description }}"</p>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl p-8 text-center border-2 border-dashed border-orange-300">
                <div class="text-6xl mb-4">🤷</div>
                <p class="text-lg font-bold text-gray-700 mb-2">No activity yet!</p>
                <p class="text-sm text-gray-600">Click "Add Expense" to get started 🚀</p>
            </div>
        @endif
        </div>
    </div>

    <!-- Mobile Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 sm:hidden z-40 flex flex-col gap-3">
        <!-- Add Advance FAB -->
        <button onclick="openAdvanceModal()" class="inline-flex justify-center items-center w-14 h-14 bg-amber-600 text-white rounded-full hover:bg-amber-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Advance">
            <span class="text-2xl">💰</span>
        </button>
        <!-- View Members FAB -->
        <a href="{{ route('groups.members', $group) }}" class="inline-flex justify-center items-center w-14 h-14 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="View Members">
            <span class="text-2xl">👥</span>
        </a>
        <!-- Add Expense FAB -->
        <a href="{{ route('groups.expenses.create', $group) }}" class="inline-flex justify-center items-center w-14 h-14 bg-green-600 text-white rounded-full hover:bg-green-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Expense">
            <span class="text-2xl">+</span>
        </a>
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
// Toggle section collapse/expand
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const toggle = document.getElementById(sectionId + 'Toggle');

    if (section.classList.contains('hidden')) {
        // Show section
        section.classList.remove('hidden');
        toggle.textContent = '▼';
        toggle.style.transform = 'rotate(0deg)';
    } else {
        // Hide section
        section.classList.add('hidden');
        toggle.textContent = '▶';
        toggle.style.transform = 'rotate(0deg)';
    }
}

// Open Advance Modal
function openAdvanceModal() {
    document.getElementById('advanceModal').classList.remove('hidden');
    document.getElementById('advanceModal').classList.add('flex');
}

function closeAdvanceModal(event) {
    if (!event || event.target.id === 'advanceModal') {
        document.getElementById('advanceModal').classList.add('hidden');
        document.getElementById('advanceModal').classList.remove('flex');
    }
}

function openGroupPaymentModal(splitId, payeeName, amount, expenseTitle) {
    document.getElementById('groupPayeeName').textContent = payeeName;
    document.getElementById('groupPaymentAmount').textContent = '$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('groupPaymentForm').action = '/splits/' + splitId + '/mark-paid';
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

function openAdvancesInfoModal() {
    document.getElementById('advancesInfoModal').classList.remove('hidden');
    document.getElementById('advancesInfoModal').classList.add('flex');
}

function closeAdvancesInfoModal(event) {
    if (!event || event.target.id === 'advancesInfoModal') {
        document.getElementById('advancesInfoModal').classList.add('hidden');
        document.getElementById('advancesInfoModal').classList.remove('flex');
    }
}
</script>

<!-- Add Advance Modal -->
<div id="advanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto" onclick="closeAdvanceModal(event)">
    <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 my-8" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                <span>💰</span>
                Record Advance Payment
            </h3>
            <button onclick="closeAdvanceModal()" class="text-gray-500 hover:text-gray-700 text-2xl font-bold">✕</button>
        </div>

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
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($group->members as $member)
                        <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                            <input type="checkbox" name="senders[]" value="{{ $member->id }}" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                            <span class="text-gray-700">{{ $member->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAdvanceModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-bold">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:from-amber-600 hover:to-orange-600 transition-all font-bold">
                    ➕ Record Advance
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Advances Info Modal -->
<div id="advancesInfoModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="closeAdvancesInfoModal(event)">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl">💰</span>
            <h3 class="text-xl font-bold text-gray-900">What is an Advance?</h3>
        </div>

        <div class="space-y-4">
            <p class="text-gray-700">
                An <strong>Advance</strong> is when someone pays a large amount upfront that benefits the whole group.
            </p>

            <div>
                <p class="font-semibold text-green-600 mb-2">✅ Examples of Advances:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 ml-2">
                    <li>Hotel booking paid by one person</li>
                    <li>Rental car paid upfront</li>
                    <li>Group activity/tour ticket purchased by one person</li>
                    <li>Gas/travel costs paid in advance</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-red-600 mb-2">❌ NOT an Advance:</p>
                <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 ml-2">
                    <li>Dinner split between friends (use "Add Expense")</li>
                    <li>Coffee for one person (use "Add Expense")</li>
                </ul>
            </div>

            <p class="text-sm bg-amber-50 border-l-4 border-amber-500 p-3 text-amber-800">
                <strong>💡 Note:</strong> Advances automatically reduce what each person owes in the settlement calculation.
            </p>
        </div>

        <button onclick="closeAdvancesInfoModal()" class="mt-6 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold">
            Got it!
        </button>
    </div>
</div>

    </div>
</div>

<!-- Mobile Floating Action Buttons -->
<x-group-fabs :group="$group" :showPdfExport="true" />

@endsection
