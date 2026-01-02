@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <!-- Hero Section -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border-b border-gray-200">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-2">
                        Welcome back, {{ explode(' ', auth()->user()->name)[0] }}
                    </h1>
                    <p class="text-lg text-gray-600">
                        Track and settle expenses with your friends
                    </p>
                </div>
                <!-- Desktop Create Squad Button -->
                <a href="{{ route('groups.create') }}" class="hidden sm:inline-flex justify-center items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold shadow-lg">
                    <span class="mr-2">+</span>
                    Create Group
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Summary Cards - Multi-Currency Support -->
            @php
                $currencySymbols = [
                    'USD' => '$',
                    'EUR' => '€',
                    'GBP' => '£',
                    'INR' => '₹',
                    'AUD' => 'A$',
                    'CAD' => 'C$',
                ];
            @endphp
            
            <!-- Show balances for each currency -->
            @foreach($balancesByCurrency as $currency => $balances)
                @if($balances['you_owe'] > 0 || $balances['they_owe'] > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-700 mb-3">{{ $currency }} Balances</h3>
                        <div class="grid grid-cols-3 gap-2 sm:gap-4 md:gap-6">
                            <!-- You Owe -->
                            <button onclick="openBalanceModal('you_owe', '{{ $currency }}', {{ json_encode($settlementDetailsByCurrency[$currency]['you_owe_breakdown'] ?? []) }}, '{{ $currencySymbols[$currency] ?? $currency }}')" class="bg-white rounded-lg shadow-sm border border-red-200 p-3 sm:p-4 md:p-6 hover:shadow-md hover:border-red-400 transition-all cursor-pointer text-left">
                                <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">You Owe</h3>
                                    <span class="text-lg sm:text-2xl">📤</span>
                                </div>
                                <p class="text-xl sm:text-2xl md:text-3xl font-bold text-red-600 mb-1 sm:mb-2">
                                    {{ $currencySymbols[$currency] ?? $currency }}{{ formatCurrency($balances['you_owe']) }}
                                </p>
                                <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                                    Click to see details
                                </p>
                            </button>

                            <!-- They Owe You -->
                            <button onclick="openBalanceModal('they_owe', '{{ $currency }}', {{ json_encode($settlementDetailsByCurrency[$currency]['they_owe_breakdown'] ?? []) }}, '{{ $currencySymbols[$currency] ?? $currency }}')" class="bg-white rounded-lg shadow-sm border border-green-200 p-3 sm:p-4 md:p-6 hover:shadow-md hover:border-green-400 transition-all cursor-pointer text-left">
                                <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">They Owe You</h3>
                                    <span class="text-lg sm:text-2xl">📥</span>
                                </div>
                                <p class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600 mb-1 sm:mb-2">
                                    {{ $currencySymbols[$currency] ?? $currency }}{{ formatCurrency($balances['they_owe']) }}
                                </p>
                                <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                                    Click to see details
                                </p>
                            </button>

                            <!-- Net Balance -->
                            <button onclick="openBalanceModal('{{ $balances['net'] >= 0 ? 'they_owe' : 'you_owe' }}', '{{ $currency }}', {{ json_encode($balances['net'] >= 0 ? ($settlementDetailsByCurrency[$currency]['they_owe_breakdown'] ?? []) : ($settlementDetailsByCurrency[$currency]['you_owe_breakdown'] ?? [])) }}, '{{ $currencySymbols[$currency] ?? $currency }}')" class="bg-white rounded-lg shadow-sm border {{ $balances['net'] >= 0 ? 'border-green-200 hover:border-green-400' : 'border-red-200 hover:border-red-400' }} p-3 sm:p-4 md:p-6 hover:shadow-md transition-all cursor-pointer text-left">
                                <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                                    <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">Your Balance</h3>
                                    <span class="text-lg sm:text-2xl">{{ $balances['net'] >= 0 ? '✅' : '⚠️' }}</span>
                                </div>
                                <p class="text-xl sm:text-2xl md:text-3xl font-bold {{ $balances['net'] >= 0 ? 'text-green-600' : 'text-red-600' }} mb-1 sm:mb-2">
                                    {{ $balances['net'] >= 0 ? '+' : '' }}{{ $currencySymbols[$currency] ?? $currency }}{{ formatCurrency(abs($balances['net'])) }}
                                </p>
                                <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                                    Click to see details
                                </p>
                            </button>
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- You Owe Breakdown -->
            @if($pendingPayments->count() > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">You Owe</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($pendingPayments as $payment)
                        <div class="bg-white rounded-lg shadow-sm border border-red-200 p-5 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-red-700">{{ strtoupper(substr($payment->split->expense->payer->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $payment->split->expense->payer->name }}</p>
                                        @if($payment->split->expense->group)
                                            <p class="text-xs text-gray-500">{{ $payment->split->expense->group->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <p class="text-xs text-gray-600 mb-1">{{ $payment->split->expense->title }}</p>
                                <p class="text-2xl font-bold text-red-600">₹{{ number_format($payment->split->share_amount, 0) }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button data-payment-id="{{ $payment->id }}"
                                        data-payer-name="{{ $payment->split->expense->payer->name }}"
                                        data-amount="{{ $payment->split->share_amount }}"
                                        data-title="{{ $payment->split->expense->title }}"
                                        class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-sm open-payment-modal">
                                    Settle
                                </button>
                                @if($payment->split->expense->group)
                                    <a href="{{ route('groups.expenses.show', [$payment->split->expense->group, $payment->split->expense]) }}"
                                       class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all font-semibold text-sm">
                                        Details
                                    </a>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-3">{{ $payment->split->expense->date->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Already Paid Breakdown - HIDDEN -->
            <!--
            @if(count($paidPayments) > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Settled</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($paidPayments as $payment)
                    <div class="bg-white rounded-lg shadow-sm border border-green-200 p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-green-700">{{ strtoupper(substr($payment->split->expense->payer->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $payment->split->expense->payer->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $payment->split->expense->group->name }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Paid</span>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-gray-600 mb-1">{{ $payment->split->expense->title }}</p>
                            <p class="text-2xl font-bold text-green-600">₹{{ number_format($payment->split->share_amount, 0) }}</p>
                        </div>
                        <p class="text-xs text-gray-500">{{ $payment->paid_date ? \Carbon\Carbon::parse($payment->paid_date)->format('M d, Y') : $payment->created_at->format('M d, Y') }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            -->

            <!-- Friends Owe Me Breakdown -->
            @if(count($peopleOweMe) > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">You Are Owed</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($peopleOweMe as $person)
                    <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-blue-700">{{ strtoupper(substr($person['user']->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $person['user']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $person['payment_count'] }} {{ $person['payment_count'] == 1 ? 'payment' : 'payments' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <p class="text-xs text-gray-600 mb-1">Owes you</p>
                            <p class="text-2xl font-bold text-blue-600">₹{{ number_format($person['total_owed'], 0) }}</p>
                        </div>
                        <button class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-sm">
                            Remind
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

    <!-- Analytics Dashboard - HIDDEN FOR NOW -->
    <!--
    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-4 sm:p-6">
        <h2 class="text-xl sm:text-2xl font-black text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">📊</span>
            <span>Quick Analytics</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-md">
                <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-1">
                    <span class="text-lg">🎯</span>
                    <span>Net Balance</span>
                </h3>
                @php
                    $theyOweMe = $totalTheyOweYou;
                @endphp
                @if($totalYouOwe > 0 || $theyOweMe > 0)
                    <div class="relative" style="max-width: 180px; max-height: 180px; margin: 0 auto;">
                        <canvas id="balance-donut" width="180" height="180"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <p class="text-2xl font-black text-gray-900">₹{{ number_format($totalYouOwe + $theyOweMe, 0) }}</p>
                            <p class="text-xs font-semibold text-gray-600">Total</p>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="font-semibold">You Owe</span>
                            </span>
                            <span class="font-bold text-red-600">₹{{ number_format($totalYouOwe, 0) }}</span>
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
                    <script nonce="{{ request()->attributes->get('nonce', '') }}">
                    (function() {
                        const ctx = document.getElementById('balance-donut').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['You Owe', 'They Owe You'],
                                datasets: [{
                                    data: [{{ $totalYouOwe }}, {{ $theyOweMe }}],
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

            <div class="bg-white rounded-xl p-4 shadow-md">
                <h3 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="text-lg">📊</span>
                    <span>Breakdown</span>
                </h3>
                <div class="space-y-3">
                    <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-lg p-3 border border-red-200">
                        <p class="text-xs font-semibold text-red-700 mb-1">You Owe</p>
                        <p class="text-2xl font-black text-red-600">₹{{ number_format($totalYouOwe, 0) }}</p>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-3 border border-green-200">
                        <p class="text-xs font-semibold text-green-700 mb-1">They Owe You</p>
                        <p class="text-2xl font-black text-green-600">₹{{ number_format($totalTheyOweYou, 0) }}</p>
                    </div>
                </div>
            </div>

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
    -->

            <!-- Your Groups -->
            @if(count($groups) > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Groups</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($groups as $item)
                        <a href="{{ route('groups.show', $item['group']) }}" class="group block">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-lg hover:border-blue-300 transition-all hover:-translate-y-1">
                                <!-- Header with Icon and Name -->
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl flex-shrink-0 shadow-md">
                                        {{ $item['group']->icon ?? '👥' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 truncate text-base group-hover:text-blue-600 transition-colors">{{ $item['group']->name }}</h3>
                                        @if($item['user_is_admin'])
                                            <span class="inline-block px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">Admin</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Stats in compact row -->
                                <div class="flex items-center justify-between text-sm bg-gray-50 rounded-lg p-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-blue-600 font-semibold">💰</span>
                                        <span class="text-gray-600 text-xs">Expenses:</span>
                                        <span class="font-bold text-gray-900">{{ $item['total_expenses'] }}</span>
                                    </div>
                                    <div class="w-px h-4 bg-gray-300"></div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-green-600 font-semibold">👥</span>
                                        <span class="text-gray-600 text-xs">Members:</span>
                                        <span class="font-bold text-gray-900">{{ $item['group']->members()->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl shadow-sm border border-blue-200 p-8 text-center">
                <div class="text-5xl mb-3">👥</div>
                <p class="text-gray-900 font-bold mb-1">No groups yet</p>
                <p class="text-sm text-gray-600">Create a group to start tracking expenses with friends</p>
            </div>
            @endif

            <!-- Recent Expenses -->
            @if(count($recentExpenses) > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Recent Expenses</h2>
                <div class="space-y-3">
                    @foreach($recentExpenses as $expense)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900">{{ $expense->title }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ $expense->payer->name }} • {{ $expense->group->name }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $expense->date->format('M d, Y') }}</p>
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <p class="font-bold text-gray-900">₹{{ number_format($expense->amount, 0) }}</p>
                                    <span class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $expense->split_type)) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Mobile Floating Action Button for Create Squad -->
    <a href="{{ route('groups.create') }}" class="fixed bottom-6 right-6 inline-flex justify-center items-center sm:hidden w-14 h-14 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all transform hover:scale-110 font-bold shadow-lg z-40" title="Create Group">
        <span class="text-2xl">+</span>
    </a>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" data-close-modal="true">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-lg" data-stop-propagation="true">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Settle Payment</h3>

        <form id="paymentForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-xs text-gray-600 font-semibold mb-2">PAYING TO</p>
                <p class="text-lg font-bold text-gray-900 mb-4" id="payeeName"></p>

                <p class="text-xs text-gray-600 font-semibold mb-2">FOR</p>
                <p class="font-semibold text-gray-900 mb-4" id="expenseTitle"></p>

                <p class="text-xs text-gray-600 font-semibold mb-2">AMOUNT</p>
                <p class="text-3xl font-bold text-blue-600" id="paymentAmount"></p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Payment Notes (Optional)</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm" placeholder="e.g., Paid via UPI"></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Receipt (Optional)</label>
                <input type="file" name="receipt" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 text-sm">
            </div>

            <div class="flex gap-3">
                <button type="button" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all font-semibold text-sm close-payment-modal">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-sm">
                    Settle
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Modal (for Mark as Paid from balance) -->
<div id="paymentModalFromBalance" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" data-close-modal="true">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4" data-stop-propagation="true">
        <h3 class="text-2xl font-black text-gray-900 mb-4">Mark Payment as Paid</h3>

        <form id="paymentFormFromBalance" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                <p class="text-sm text-gray-600">Paying to:</p>
                <p class="text-lg font-black text-gray-900" id="payeeNameFromBalance"></p>
                <p class="text-3xl font-black text-green-600 mt-2" id="paymentAmountFromBalance"></p>
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
                <button type="button" onclick="closePaymentModalFromBalance()" class="flex-1 px-4 py-3 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all font-bold">
                    ✓ Mark as Paid
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Balance Details Modal -->
<div id="balanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg max-w-2xl w-full mx-4 shadow-lg my-8" data-stop-propagation="true">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-white border-b border-gray-200 p-6 flex items-center justify-between rounded-t-lg">
            <div>
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-900"></h2>
                <p id="modalSubtitle" class="text-sm text-gray-600 mt-1"></p>
            </div>
            <button onclick="closeBalanceModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">
                ✕
            </button>
        </div>

        <!-- Modal Content -->
        <div id="modalContent" class="p-6 space-y-4 max-h-96 overflow-y-auto">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Modal Footer -->
        <div class="bg-gray-50 border-t border-gray-200 p-4 rounded-b-lg flex gap-3">
            <button onclick="closeBalanceModal()" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all font-semibold">
                Close
            </button>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get('nonce', '') }}">
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

// Balance Modal Functions
function openBalanceModal(type, currency, breakdown, currencySymbol) {
    const modal = document.getElementById('balanceModal');
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');
    const content = document.getElementById('modalContent');

    // Set title and subtitle based on type
    if (type === 'you_owe') {
        title.textContent = 'Amount You Owe';
        subtitle.textContent = `Details of payments owed in ${currency}`;
    } else {
        title.textContent = 'Amount Owed to You';
        subtitle.textContent = `Details of payments owed to you in ${currency}`;
    }

    // Generate breakdown HTML
    if (breakdown.length === 0) {
        content.innerHTML = '<div class="text-center py-8 text-gray-500">No balances found</div>';
    } else {
        // Group by person
        const groupedByPerson = {};
        breakdown.forEach(item => {
            const personName = item.person.name;
            const amount = parseFloat(item.amount);

            // Skip zero amounts
            if (Math.abs(amount) < 0.01) {
                return;
            }

            if (!groupedByPerson[personName]) {
                groupedByPerson[personName] = {
                    person: item.person,
                    total: 0,
                    groups: []
                };
            }
            groupedByPerson[personName].total += amount;
            groupedByPerson[personName].groups.push(item);
        });

        // Check if there are any non-zero balances
        if (Object.keys(groupedByPerson).length === 0) {
            content.innerHTML = '<div class="text-center py-8 text-gray-500">No outstanding balances</div>';
        } else {

        content.innerHTML = Object.entries(groupedByPerson).map(([personName, data]) => `
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow person-balance-card" data-person-name="${personName}" data-type="${type}" data-currency="${currency}" data-currency-symbol="${currencySymbol}" data-balance-data='${JSON.stringify(data)}'>
                <!-- Person Header -->
                <div class="flex items-center justify-between mb-4 cursor-pointer" onclick="togglePersonDetails(this)">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 rounded-full ${type === 'you_owe' ? 'bg-red-100' : 'bg-green-100'} flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold ${type === 'you_owe' ? 'text-red-700' : 'text-green-700'}">
                                ${personName.charAt(0).toUpperCase()}
                            </span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900">${personName}</p>
                            <p class="text-xs ${data.groups.length > 1 ? 'text-blue-600 font-semibold' : 'text-gray-500'}">
                                ${data.groups.length} group${data.groups.length > 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-lg font-bold ${type === 'you_owe' ? 'text-red-600' : 'text-green-600'}">
                            ${currencySymbol}${data.total.toFixed(2)}
                        </p>
                        <p class="text-xs font-semibold ${type === 'you_owe' ? 'text-red-600' : 'text-green-600'} mt-1">
                            ${type === 'you_owe' ? '💳 You Need to Pay' : '💰 They Need to Pay'}
                        </p>
                    </div>
                </div>

                <!-- Groups Breakdown - Initially collapsed -->
                <div class="text-sm details-content" style="display: none;">
                    <div class="text-xs text-gray-600 font-semibold mb-3 flex items-center gap-2">
                        📋 View by group
                    </div>
                    <div class="mt-3 space-y-3">
                        ${data.groups.map(item => `
                            <div class="bg-gray-50 rounded p-3 border border-gray-100">
                                <!-- Group Header -->
                                <div class="flex justify-between items-center mb-2">
                                    <p class="text-xs font-semibold text-gray-700">${item.group_name}</p>
                                    <p class="font-bold ${type === 'you_owe' ? 'text-red-600' : 'text-green-600'}">${currencySymbol}${parseFloat(item.amount).toFixed(2)}</p>
                                </div>

                                <!-- Expenses in this group -->
                                <details class="text-xs">
                                    <summary class="text-gray-600 font-semibold cursor-pointer hover:text-gray-900 transition-colors">
                                        ${item.expense_count} transaction${item.expense_count > 1 ? 's' : ''}
                                    </summary>
                                    <div class="mt-2 pl-3 border-l-2 border-gray-300 space-y-1">
                                        ${(item.expenses || []).filter(exp => Math.abs(parseFloat(exp.amount)) >= 0.01).map(exp => {
                                            // Determine color based on who paid
                                            // In 'you_owe' section: type shows if other person paid (they_owe) or you paid (you_owe)
                                            // In 'they_owe' section: opposite logic
                                            const isPaidByOtherPerson = (type === 'you_owe' && exp.type === 'you_owe') || (type === 'they_owe' && exp.type === 'they_owe');
                                            const color = isPaidByOtherPerson ? 'text-red-600' : 'text-green-600';

                                            return `
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="truncate flex-1 text-gray-700">${exp.title}</span>
                                                <span class="font-bold flex-shrink-0 ml-2 ${color}">${currencySymbol}${parseFloat(exp.amount).toFixed(2)}</span>
                                            </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </details>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- Mark as Paid Button -->
                ${type === 'you_owe' ? `
                    <button class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-sm open-settlement-payment-modal mt-4"
                            data-group-id="${data.groups[0].group_id}"
                            data-payee-id="${data.person.id}"
                            data-split-ids='${JSON.stringify(data.groups.flatMap(g => g.split_ids || []))}'
                            data-person-name="${personName}"
                            data-amount="${data.total.toFixed(2)}"
                            data-currency="${currencySymbol}">
                        ${data.groups.length > 1 ? '✓ Settle Payment (across ' + data.groups.length + ' groups)' : '✓ Mark as Paid'}
                    </button>
                ` : ''}
            </div>
        `).join('');
        }
    }

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function togglePersonDetails(headerElement) {
    // Get the parent card
    const card = headerElement.closest('.person-balance-card');
    const detailsContent = card.querySelector('.details-content');

    if (detailsContent.style.display === 'none') {
        detailsContent.style.display = 'block';
    } else {
        detailsContent.style.display = 'none';
    }
}

function closeBalanceModal() {
    const modal = document.getElementById('balanceModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Mark settlement as paid - open modal with payment form
function markSettlementAsPaid(groupId, splitIds, personName, amount, button, payeeId) {
    // Ensure splitIds is an array
    let idsArray = splitIds;
    if (typeof splitIds === 'string') {
        try {
            idsArray = JSON.parse(splitIds);
        } catch (e) {
            console.error('Failed to parse split_ids:', splitIds, e);
            idsArray = [];
        }
    }

    // Open payment modal even if split_ids is empty (for manual settlements)
    openPaymentModalFromBalance(idsArray, personName, amount, groupId, payeeId);

    // Close the balance details modal
    closeBalanceModal();
}

// Open payment modal from balance details
function openPaymentModalFromBalance(splitIds, payeeName, amount, groupId, payeeId) {
    const modal = document.getElementById('paymentModalFromBalance');
    const form = document.getElementById('paymentFormFromBalance');

    // Set display info
    document.getElementById('payeeNameFromBalance').textContent = payeeName;
    document.getElementById('paymentAmountFromBalance').textContent = '$' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Remove any existing split_ids, payee_id, group_id inputs
    form.querySelectorAll('input[name="split_ids[]"]').forEach(input => input.remove());
    form.querySelectorAll('input[name="payee_id"]').forEach(input => input.remove());
    form.querySelectorAll('input[name="group_id"]').forEach(input => input.remove());
    form.querySelectorAll('input[name="payment_amount"]').forEach(input => input.remove());

    // Add hidden inputs for all split IDs (only if there are actual splits)
    if (splitIds && splitIds.length > 0) {
        splitIds.forEach(splitId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'split_ids[]';
            input.value = splitId;
            form.appendChild(input);
        });
    }

    // If no split IDs, add payee and group info for manual settlement
    if (splitIds.length === 0 && payeeId) {
        const payeeInput = document.createElement('input');
        payeeInput.type = 'hidden';
        payeeInput.name = 'payee_id';
        payeeInput.value = payeeId;
        form.appendChild(payeeInput);

        const groupInput = document.createElement('input');
        groupInput.type = 'hidden';
        groupInput.name = 'group_id';
        groupInput.value = groupId;
        form.appendChild(groupInput);

        const amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'payment_amount';
        amountInput.value = parseFloat(amount);
        form.appendChild(amountInput);
    }

    // Set form action to batch payment endpoint
    form.action = '/payments/mark-paid-batch';

    modal.classList.remove('hidden');
}

function closePaymentModalFromBalance(event) {
    if (!event || event.target.id === 'paymentModalFromBalance') {
        const modal = document.getElementById('paymentModalFromBalance');
        modal.classList.add('hidden');
    }
}

// Settlement payment modal buttons (from balance details modal)
// Use event delegation since buttons are created dynamically
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('open-settlement-payment-modal')) {
        e.preventDefault();
        const btn = e.target;
        const groupId = btn.dataset.groupId;
        const payeeId = btn.dataset.payeeId;
        const splitIds = btn.dataset.splitIds;
        const personName = btn.dataset.personName;
        const amount = btn.dataset.amount;
        markSettlementAsPaid(groupId, splitIds, personName, amount, btn, payeeId);
    }
});

// Event listeners for payment modal
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission for payment from balance - reload page on success
    const paymentFormFromBalance = document.getElementById('paymentFormFromBalance');
    if (paymentFormFromBalance) {
        paymentFormFromBalance.addEventListener('submit', function(e) {
            e.preventDefault();

            // Submit form and reload page when response completes
            const formData = new FormData(this);
            const action = this.action;

            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Payment successful, reload to show updated balance
                    location.reload();
                } else {
                    // Server returned an error
                    return response.text().then(text => {
                        console.error('Payment error response:', text);
                        alert('Failed to submit payment. Please try again.');
                    });
                }
            })
            .catch(error => {
                console.error('Error submitting payment:', error);
                alert('Failed to submit payment. Please try again.');
            });
        });
    }

    // Open modal buttons
    document.querySelectorAll('.open-payment-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const paymentId = this.dataset.paymentId;
            const payerName = this.dataset.payerName;
            const amount = this.dataset.amount;
            const title = this.dataset.title;
            openPaymentModal(paymentId, payerName, amount, title);
        });
    });

    // Close modal buttons
    document.querySelectorAll('.close-payment-modal').forEach(btn => {
        btn.addEventListener('click', closePaymentModal);
    });

    // Close modal when clicking backdrop
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('click', function(e) {
            if (e.target.id === 'paymentModal') {
                closePaymentModal(e);
            } else {
                e.stopPropagation();
            }
        });
    }

    // Close payment modal from balance when clicking backdrop
    const paymentModalFromBalance = document.getElementById('paymentModalFromBalance');
    if (paymentModalFromBalance) {
        paymentModalFromBalance.addEventListener('click', function(e) {
            if (e.target.id === 'paymentModalFromBalance') {
                closePaymentModalFromBalance(e);
            } else {
                e.stopPropagation();
            }
        });
    }

    // Close balance modal when clicking backdrop
    const balanceModal = document.getElementById('balanceModal');
    if (balanceModal) {
        balanceModal.addEventListener('click', function(e) {
            if (e.target.id === 'balanceModal') {
                closeBalanceModal();
            } else if (e.target.getAttribute('data-stop-propagation') !== 'true') {
                // Allow clicks inside modal to propagate to details/summary elements
            } else {
                e.stopPropagation();
            }
        });
    }
});
</script>
@endsection
