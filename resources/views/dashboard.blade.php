@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Hey {{ explode(' ', auth()->user()->name)[0] }}! 👋
            </h1>
            <p class="mt-2 text-gray-600">Let's see who owes you money 💰</p>
        </div>
        <a href="{{ route('groups.create') }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all transform hover:scale-105 font-semibold shadow-lg">
            <span class="text-xl mr-2">➕</span>
            Create Squad
        </a>
    </div>

    <!-- Summary Cards - Mobile Optimized -->
    @php
        $netOwed = $totalOwed - $totalPaid;
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Net Balance Card -->
        <div class="bg-gradient-to-br {{ $netOwed > 0 ? 'from-red-50 to-orange-50' : 'from-green-50 to-emerald-50' }} rounded-lg sm:rounded-xl shadow-md sm:shadow-lg p-3 sm:p-6 border-2 {{ $netOwed > 0 ? 'border-red-200' : 'border-green-200' }} transform hover:scale-105 transition-transform">
            <div class="flex flex-col">
                <p class="text-xs sm:text-sm font-bold {{ $netOwed > 0 ? 'text-red-700' : 'text-green-700' }} flex items-center gap-1 sm:gap-2 mb-1">
                    <span class="text-lg sm:text-2xl">{{ $netOwed > 0 ? '😬' : '🤑' }}</span>
                    <span>{{ $netOwed > 0 ? 'You Owe' : 'They Owe You' }}</span>
                </p>
                <p class="mt-1 sm:mt-2 text-2xl sm:text-4xl font-black {{ $netOwed > 0 ? 'text-red-600' : 'text-green-600' }}">₹{{ number_format(abs($netOwed), 0) }}</p>
                <p class="text-xs {{ $netOwed > 0 ? 'text-red-600' : 'text-green-600' }} mt-0.5 sm:mt-1">{{ $netOwed > 0 ? 'Pay your friends!' : 'You\'re awesome!' }}</p>
            </div>
        </div>

        <!-- Pending Count Card -->
        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg p-3 sm:p-6 border-2 border-yellow-200 transform hover:scale-105 transition-transform">
            <div class="flex flex-col">
                <p class="text-xs sm:text-sm font-bold text-yellow-700 flex items-center gap-1 sm:gap-2 mb-1">
                    <span class="text-lg sm:text-2xl">⏰</span>
                    <span>Pending Dues</span>
                </p>
                <p class="mt-1 sm:mt-2 text-2xl sm:text-4xl font-black text-yellow-600">{{ $pendingCount }}</p>
                <p class="text-xs text-yellow-600 mt-0.5 sm:mt-1">Items waiting</p>
            </div>
        </div>

        <!-- Squads Card -->
        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg p-3 sm:p-6 border-2 border-blue-200 transform hover:scale-105 transition-transform">
            <div class="flex flex-col">
                <p class="text-xs sm:text-sm font-bold text-blue-700 flex items-center gap-1 sm:gap-2 mb-1">
                    <span class="text-lg sm:text-2xl">👥</span>
                    <span>Your Squads</span>
                </p>
                <p class="mt-1 sm:mt-2 text-2xl sm:text-4xl font-black text-blue-600">{{ count($groups) }}</p>
                <p class="text-xs text-blue-600 mt-0.5 sm:mt-1">Friend groups</p>
            </div>
        </div>
    </div>

    <!-- You Owe Breakdown -->
    @if($pendingPayments->count() > 0)
    <div class="bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50 rounded-2xl shadow-lg p-4">
        <button onclick="toggleSection('you-owe')" class="w-full flex items-center justify-between p-2 hover:bg-white/50 rounded-xl transition-all">
            <h2 class="text-xl font-black text-gray-900 flex items-center gap-2">
                <span class="text-2xl">😬</span>
                <span>You Need to Pay These People!</span>
                <span class="px-3 py-1 bg-red-500 text-white text-sm font-bold rounded-full">{{ $pendingPayments->count() }}</span>
            </h2>
            <svg id="you-owe-icon" class="w-6 h-6 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        
        <div id="you-owe-content" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($pendingPayments as $payment)
                <div class="bg-white rounded-xl p-3 shadow-md border-2 border-red-500 hover:shadow-xl transition-all">
                    <div class="flex items-start gap-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-black text-white">{{ strtoupper(substr($payment->split->expense->payer->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-gray-900 truncate">{{ $payment->split->expense->payer->name }}</p>
                            <p class="text-xs text-gray-600 truncate">💰 {{ $payment->split->expense->title }}</p>
                            @if($payment->split->expense->group)
                                <p class="text-xs text-gray-500">🏠 {{ $payment->split->expense->group->name }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs text-gray-600">You owe</p>
                            <p class="text-xl font-black text-red-600">₹{{ number_format($payment->split->share_amount, 0) }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openPaymentModal({{ $payment->id }}, '{{ $payment->split->expense->payer->name }}', {{ $payment->split->share_amount }}, '{{ $payment->split->expense->title }}')"
                                class="flex-1 px-3 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 transition-all font-bold text-xs">
                            ✓ Mark Paid
                        </button>
                        @if($payment->split->expense->group)
                            <a href="{{ route('groups.expenses.show', [$payment->split->expense->group, $payment->split->expense]) }}"
                               class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-bold text-xs">
                                👁️
                            </a>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-2">📅 {{ $payment->split->expense->date->diffForHumans() }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Already Paid Breakdown -->
    @if(count($paidPayments) > 0)
    <div class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 rounded-2xl shadow-xl p-4 sm:p-6 border-2 border-green-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
            <h2 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2 sm:gap-3">
                <span class="text-2xl sm:text-3xl">✅</span>
                <span>You Already Paid These!</span>
            </h2>
            <span class="px-3 sm:px-4 py-1.5 sm:py-2 bg-green-500 text-white rounded-full font-bold text-sm sm:text-lg self-start sm:self-auto">
                {{ count($paidPayments) }} paid
            </span>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
            @foreach($paidPayments as $payment)
            <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-4 sm:p-5 border-2 border-green-500 hover:shadow-xl transition-all">
                <div class="flex items-start justify-between mb-2 sm:mb-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 sm:mb-2">
                            <span class="text-xl sm:text-2xl">👤</span>
                            <p class="font-bold text-gray-900 text-base sm:text-lg truncate">{{ $payment->split->expense->payer->name }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-600 mb-1">
                            <span>💰</span>
                            <p class="font-semibold truncate">{{ $payment->split->expense->title }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>👥</span>
                            <p class="truncate">{{ $payment->split->expense->group->name }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-2 sm:pt-3 border-t border-gray-200">
                    <div>
                        <p class="text-xs text-gray-500">You paid</p>
                        <p class="text-xl sm:text-2xl font-black text-green-600">₹{{ number_format($payment->split->share_amount, 0) }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="px-2 sm:px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                            ✓ Paid
                        </span>
                        <p class="text-xs text-gray-400">
                            {{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('M d') : $payment->created_at->format('M d') }}
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Friends Owe Me Breakdown -->
    @if(count($peopleOweMe) > 0)
    <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg p-4">
        <button onclick="toggleSection('friends-owe')" class="w-full flex items-center justify-between p-2 hover:bg-white/50 rounded-xl transition-all">
            <h2 class="text-xl font-black text-gray-900 flex items-center gap-2">
                <span class="text-2xl">💸</span>
                <span>Friends Owe You!</span>
                <span class="px-3 py-1 bg-cyan-500 text-white text-sm font-bold rounded-full">{{ count($peopleOweMe) }}</span>
            </h2>
            <svg id="friends-owe-icon" class="w-6 h-6 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        
        <div id="friends-owe-content" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($peopleOweMe as $person)
            <div class="bg-white rounded-xl shadow-md p-3 border-2 border-cyan-500 hover:shadow-xl transition-all">
                <div class="flex items-start gap-2 mb-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-400 to-blue-400 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-black text-white">{{ strtoupper(substr($person['user']->name, 0, 1)) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-gray-900 truncate">{{ $person['user']->name }}</p>
                        <p class="text-xs text-gray-500">{{ $person['payment_count'] }} {{ $person['payment_count'] == 1 ? 'payment' : 'payments' }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-xs text-gray-600">Owes you</p>
                        <p class="text-xl font-black text-cyan-600">₹{{ number_format($person['total_owed'], 0) }}</p>
                    </div>
                </div>
                <button class="w-full px-3 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-lg font-bold hover:from-cyan-600 hover:to-blue-600 transition-all text-xs">
                    Remind 📱
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Analytics Dashboard - Compact -->
    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-4 sm:p-6">
        <h2 class="text-xl sm:text-2xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">📊</span>
            <span>Quick Analytics</span>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Balance Overview -->
            <div class="bg-white rounded-xl p-4 shadow-md">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-1">
                    <span class="text-lg">🎯</span>
                    <span>Net Balance</span>
                </h3>
                @php
                    $theyOweMe = $peopleOweMe->sum('total_owed');
                    $netBalance = $totalOwed - $theyOweMe;
                @endphp
                @if($totalOwed > 0 || $theyOweMe > 0)
                    <div class="relative" style="max-width: 180px; max-height: 180px; margin: 0 auto;">
                        <canvas id="balance-donut" width="180" height="180"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-2xl font-black text-gray-900">₹{{ number_format($totalOwed + $theyOweMe, 0) }}</p>
                            <p class="text-xs font-semibold text-gray-600">Total</p>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="font-semibold">You Owe</span>
                            </span>
                            <span class="font-bold text-red-600">₹{{ number_format($totalOwed, 0) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="font-semibold">They Owe</span>
                            </span>
                            <span class="font-bold text-green-600">₹{{ number_format($theyOweMe, 0) }}</span>
                        </div>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                    (function() {
                        const ctx = document.getElementById('balance-donut').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['You Owe', 'They Owe You'],
                                datasets: [{
                                    data: [{{ $totalOwed }}, {{ $theyOweMe }}],
                                    backgroundColor: ['#EF4444', '#10B981'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                cutout: '70%',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': ₹' + context.parsed.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    })();
                    </script>
                @else
                    <div class="text-center py-8">
                        <p class="text-4xl mb-2">🎉</p>
                        <p class="text-sm font-bold text-gray-600">All Clear!</p>
                    </div>
                @endif
            </div>
            
            <!-- Breakdown Card -->
            <div class="bg-white rounded-xl p-4 shadow-md">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-lg">📊</span>
                    <span>Breakdown</span>
                </h3>
                <div class="space-y-3">
                    <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-lg p-3 border border-red-200">
                        <p class="text-xs font-semibold text-red-700 mb-1">You Owe</p>
                        <p class="text-2xl font-black text-red-600">₹{{ number_format($totalOwed, 0) }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                        <p class="text-xs font-semibold text-green-700 mb-1">They Owe You</p>
                        <p class="text-2xl font-black text-green-600">₹{{ number_format($theyOweMe, 0) }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="bg-white rounded-xl p-4 shadow-md space-y-3">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-1">
                    <span class="text-lg">⚡</span>
                    <span>Quick Stats</span>
                </h3>
                
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-3 border border-green-200">
                    <p class="text-xs font-bold text-green-700 mb-1">💰 Total Groups</p>
                    <p class="text-2xl font-black text-green-900">{{ count($groups) }}</p>
                </div>
                
                <div class="bg-gradient-to-r from-red-50 to-orange-50 rounded-lg p-3 border border-red-200">
                    <p class="text-xs font-bold text-red-700 mb-1">😬 Pending</p>
                    <p class="text-2xl font-black text-red-900">{{ $pendingCount }}</p>
                </div>
                
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg p-3 border border-blue-200">
                    <p class="text-xs font-bold text-blue-700 mb-1">📊 Recent</p>
                    <p class="text-2xl font-black text-blue-900">{{ count($recentExpenses) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Your Groups -->
            <div class="bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50 rounded-2xl shadow-lg p-6 border-2 border-purple-200">
                <h2 class="text-lg sm:text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🎉</span>
                    Your Squads
                </h2>

                @if(count($groups) > 0)
                    <div class="space-y-3">
                        @foreach($groups as $item)
                            <a href="{{ route('groups.show', $item['group']) }}" class="group block">
                                <div class="p-4 border border-gray-200 rounded-xl hover:border-purple-400 hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all transform hover:scale-102">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-gray-900 group-hover:text-purple-600 truncate flex items-center gap-2">
                                                <span class="text-2xl">{{ $item['group']->icon ?? '🎉' }}</span>
                                                {{ $item['group']->name }}
                                                @if($item['user_is_admin'])
                                                    <span class="inline-block px-2 py-1 bg-gradient-to-r from-amber-400 to-orange-400 text-white text-xs font-bold rounded-full">👑 Boss</span>
                                                @endif
                                            </h3>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $item['group']->description ?: 'No description' }}</p>
                                        </div>
                                        <div class="flex-shrink-0 text-right">
                                            <p class="text-xs font-semibold text-blue-600">{{ $item['total_expenses'] }} expenses</p>
                                            <p class="text-xs font-semibold text-purple-600">{{ $item['group']->members()->count() }} squad members</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20h12a6 6 0 00-6-6 6 6 0 00-6 6z" />
                        </svg>
                        <p class="text-gray-600 font-medium">No groups yet</p>
                        <p class="text-sm text-gray-500 mt-1">Create or join a group to get started</p>
                    </div>
                @endif
            </div>

            <!-- Recent Expenses -->
            <div class="bg-gradient-to-br from-orange-50 via-yellow-50 to-amber-50 rounded-2xl shadow-lg p-6 border-2 border-orange-200">
                <h2 class="text-lg sm:text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-amber-600 mb-4 flex items-center gap-2">
                    <span class="text-2xl">🔥</span>
                    Recent Action
                </h2>

                @if(count($recentExpenses) > 0)
                    <div class="space-y-3">
                        @foreach($recentExpenses as $expense)
                            <div class="p-4 bg-gradient-to-r from-white to-orange-50 border-2 border-orange-200 rounded-xl hover:shadow-lg hover:scale-102 transition-all">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate flex items-center gap-2">
                                            <span>💸</span>
                                            {{ $expense->title }}
                                        </h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium">{{ $expense->payer->name }}</span>
                                            <span class="text-gray-400">•</span>
                                            <span>{{ $expense->group->name }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $expense->date->format('M d, Y') }}</p>
                                    </div>
                                    <div class="flex-shrink-0 text-right">
                                        <p class="font-semibold text-gray-900">${{ number_format($expense->amount, 2) }}</p>
                                        <span class="inline-block mt-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                            {{ ucfirst(str_replace('_', ' ', $expense->split_type)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-600">No recent expenses</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Pending Payments List -->
            @if(count($pendingPayments) > 0)
                <div class="bg-gradient-to-br from-pink-50 via-rose-50 to-red-50 rounded-2xl shadow-lg p-6 border-2 border-pink-200">
                    <h2 class="text-lg font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-red-600 mb-4 flex items-center gap-2">
                        <span class="text-2xl">⚡</span>
                        Pay ASAP!
                    </h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($pendingPayments as $payment)
                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p class="font-semibold text-gray-900 text-sm">{{ $payment->split->expense->title }}</p>
                                <p class="text-xs text-gray-600 mt-1">{{ $payment->split->expense->payer->name }}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="font-bold text-red-600">${{ number_format($payment->split->share_amount, 2) }}</p>
                                    <form action="{{ route('logout') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="button" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Mark Paid
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-gradient-to-br from-green-100 to-emerald-100 rounded-2xl shadow-lg p-6 border-2 border-green-300">
                    <div class="flex items-center gap-3">
                        <span class="text-4xl">🎊</span>
                        <div>
                            <p class="font-black text-green-900 text-lg">All Caught Up!</p>
                            <p class="text-sm font-semibold text-green-700">You're a payment superstar! ⭐</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg p-6 border-2 border-cyan-200">
                <h2 class="text-lg font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-indigo-600 mb-4 flex items-center gap-2">
                    <span class="text-2xl">📊</span>
                    Your Stats
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-3 px-4 bg-gradient-to-r from-purple-100 to-pink-100 rounded-lg border-2 border-purple-200">
                        <span class="text-sm font-bold text-purple-700 flex items-center gap-2">
                            <span>👥</span>
                            Total Squads
                        </span>
                        <span class="font-black text-purple-900 text-xl">{{ count($groups) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 px-4 bg-gradient-to-r from-red-100 to-orange-100 rounded-lg border-2 border-red-200">
                        <span class="text-sm font-bold text-red-700 flex items-center gap-2">
                            <span>⏰</span>
                            Pending
                        </span>
                        <span class="font-black text-red-900 text-xl">{{ $pendingCount }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 px-4 bg-gradient-to-r from-blue-100 to-cyan-100 rounded-lg border-2 border-blue-200">
                        <span class="text-sm font-bold text-blue-700 flex items-center gap-2">
                            <span>🎂</span>
                            Member Since
                        </span>
                        <span class="font-black text-blue-900 text-sm">{{ auth()->user()->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closePaymentModal(event)">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <h3 class="text-2xl font-black text-gray-900 mb-4">Mark Payment as Paid</h3>
        
        <form id="paymentForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                <p class="text-sm text-gray-600">Paying to:</p>
                <p class="text-lg font-black text-gray-900" id="payeeName"></p>
                <p class="text-sm text-gray-600 mt-2">For:</p>
                <p class="font-bold text-gray-900" id="expenseTitle"></p>
                <p class="text-3xl font-black text-green-600 mt-2" id="paymentAmount"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Payment Notes (Optional)</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200" placeholder="e.g., Paid via UPI, Reference: TXN123"></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Upload Receipt (Optional)</label>
                <input type="file" name="receipt" accept="image/*" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-1">📸 Upload a screenshot or photo of payment confirmation</p>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
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
function toggleSection(sectionId) {
    const content = document.getElementById(sectionId + '-content');
    const icon = document.getElementById(sectionId + '-icon');
    
    if (content.style.display === 'none') {
        content.style.display = 'grid';
        icon.style.transform = 'rotate(0deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(-90deg)';
    }
}

function openPaymentModal(paymentId, payeeName, amount, expenseTitle) {
    document.getElementById('payeeName').textContent = payeeName;
    document.getElementById('expenseTitle').textContent = expenseTitle;
    document.getElementById('paymentAmount').textContent = '₹' + amount.toLocaleString();
    document.getElementById('paymentForm').action = '/payments/' + paymentId + '/mark-paid';
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
}

function closePaymentModal(event) {
    if (!event || event.target.id === 'paymentModal') {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('flex');
    }
}
</script>
@endsection
