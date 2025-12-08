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
            <!-- Summary Cards - Mobile Optimized -->
            @php
                // Calculate overall balances across all groups
                $totalYouOwe = 0;
                $totalTheyOweYou = 0;

                foreach ($user->groups as $group) {
                    $balances = app('App\Services\GroupService')->getGroupBalance($group);
                    $userBalance = $balances[$user->id] ?? ['total_owed' => 0, 'total_paid' => 0];
                    $totalYouOwe += $userBalance['total_owed'];
                    $totalTheyOweYou += $userBalance['total_paid'];
                }

                $netBalance = $totalTheyOweYou - $totalYouOwe;
            @endphp
            <div class="grid grid-cols-3 gap-2 sm:gap-4 md:gap-6">
                <!-- You Owe -->
                <div class="bg-white rounded-lg shadow-sm border border-red-200 p-3 sm:p-4 md:p-6 hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">You Owe</h3>
                        <span class="text-lg sm:text-2xl">📤</span>
                    </div>
                    <p class="text-xl sm:text-2xl md:text-3xl font-bold text-red-600 mb-1 sm:mb-2">₹{{ number_format($totalYouOwe, 0) }}</p>
                    <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                        Amount owed across groups
                    </p>
                </div>

                <!-- They Owe You -->
                <div class="bg-white rounded-lg shadow-sm border border-green-200 p-3 sm:p-4 md:p-6 hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">They Owe You</h3>
                        <span class="text-lg sm:text-2xl">📥</span>
                    </div>
                    <p class="text-xl sm:text-2xl md:text-3xl font-bold text-green-600 mb-1 sm:mb-2">₹{{ number_format($totalTheyOweYou, 0) }}</p>
                    <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                        Amount owed to you
                    </p>
                </div>

                <!-- Net Balance -->
                <div class="bg-white rounded-lg shadow-sm border {{ $netBalance >= 0 ? 'border-green-200' : 'border-red-200' }} p-3 sm:p-4 md:p-6 hover:shadow-md transition-shadow">
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-2 sm:mb-4">
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 mb-1 sm:mb-0">Your Balance</h3>
                        <span class="text-lg sm:text-2xl">{{ $netBalance >= 0 ? '✅' : '⚠️' }}</span>
                    </div>
                    <p class="text-xl sm:text-2xl md:text-3xl font-bold {{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }} mb-1 sm:mb-2">
                        {{ $netBalance >= 0 ? '+' : '' }}₹{{ number_format(abs($netBalance), 0) }}
                    </p>
                    <p class="text-xs sm:text-sm text-gray-600 font-semibold hidden sm:block">
                        {{ $netBalance >= 0 ? 'You are owed' : 'You owe' }}
                    </p>
                </div>
            </div>

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
                                <button onclick="openPaymentModal({{ $payment->id }}, '{{ $payment->split->expense->payer->name }}', {{ $payment->split->share_amount }}, '{{ $payment->split->expense->title }}')"
                                        class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-sm">
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

            <!-- Already Paid Breakdown -->
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($groups as $item)
                        <a href="{{ route('groups.show', $item['group']) }}" class="group block">
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">{{ $item['group']->icon ?? '👥' }}</span>
                                            <h3 class="font-bold text-gray-900 truncate">{{ $item['group']->name }}</h3>
                                        </div>
                                        @if($item['user_is_admin'])
                                            <span class="inline-block px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded mb-2">Admin</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $item['group']->description ?: 'No description' }}</p>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <p class="text-gray-600">Expenses</p>
                                        <p class="font-bold text-gray-900">{{ $item['total_expenses'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-600">Members</p>
                                        <p class="font-bold text-gray-900">{{ $item['group']->members()->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <p class="text-gray-600 font-medium mb-2">No groups yet</p>
                <p class="text-sm text-gray-500">Create a group to start tracking expenses with friends</p>
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
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closePaymentModal(event)">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-lg" onclick="event.stopPropagation()">
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
                <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all font-semibold text-sm">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-sm">
                    Settle
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
