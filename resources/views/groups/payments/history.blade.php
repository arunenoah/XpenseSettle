@extends('layouts.app')

@section('title', 'Payment History - ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <!-- Group Breadcrumb -->
    <x-group-breadcrumb :group="$group" />

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
    <x-group-tabs :group="$group" active="history" />

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Personal Settlement Section -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Your Settlement</h2>
                @if(count($personalSettlement) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Expense</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Bill by</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">They spent for me</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">I spent for them</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Final Balance</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Details</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Action</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Attachment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($personalSettlement as $item)
                            @php
                                $isOwed = $item['net_amount'] > 0;
                                // net_amount already includes advance reductions, don't double-subtract
                                $finalAmount = abs($item['net_amount']);
                                // Always show settlements
                                $shouldShow = true;

                                // Calculate they spent for me vs I spent for them
                                $theySpentForMe = 0;
                                $iSpentForThem = 0;
                                if (isset($item['expenses']) && count($item['expenses']) > 0) {
                                    foreach ($item['expenses'] as $expense) {
                                        if ($expense['type'] === 'you_owe') {
                                            $theySpentForMe += $expense['amount'];
                                        } else {
                                            $iSpentForThem += $expense['amount'];
                                        }
                                    }
                                }
                            @endphp
                            @if($shouldShow)
                            <tr class="hover:bg-gray-50 transition-all">
                                <!-- Expense Name -->
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        @if(count($item['expenses']) > 0)
                                            @foreach($item['expenses'] as $expense)
                                                <p class="font-semibold text-gray-900 text-sm">{{ $expense['title'] }}</p>
                                            @endforeach
                                        @else
                                            <p class="font-semibold text-gray-900">Settlement</p>
                                        @endif
                                    </div>
                                </td>

                                <!-- Bill by (Person Name) -->
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($item['user']->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $item['user']->name }}</span>
                                    </div>
                                </td>

                                <!-- They spent for me -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($theySpentForMe > 0)
                                        <span class="font-bold text-red-600">${{ number_format($theySpentForMe, 2) }}</span>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>

                                <!-- I spent for them -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($iSpentForThem > 0)
                                        <span class="font-bold text-green-600">${{ number_format($iSpentForThem, 2) }}</span>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>


                                <!-- Balance (Final Amount) -->
                                <td class="px-4 sm:px-6 py-4">
                                    <p class="font-black text-lg {{ $isOwed ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $isOwed ? '$' : '-$' }}{{ number_format(abs($finalAmount), 2) }}
                                    </p>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($isOwed)
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">
                                            😬 Pending Payment
                                        </span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                            ✓ They Owe You
                                        </span>
                                    @endif
                                </td>

                                <!-- Details -->
                                <td class="px-4 sm:px-6 py-4">
                                    <button onclick="openBreakdownModal('{{ $item['user']->name }}', {{ json_encode($item) }})" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">
                                        👁️ View
                                    </button>
                                </td>

                                <!-- Action -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($isOwed && isset($item['split_ids']) && count($item['split_ids']) > 0)
                                        <button onclick="openPaymentModal('{{ $item['split_ids'][0] }}', '{{ addslashes($item['user']->name) }}', '{{ $finalAmount }}')" class="inline-flex items-center gap-1 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all text-xs font-bold">
                                            💳 Mark as paid
                                        </button>
                                    @elseif($isOwed)
                                        <span class="inline-flex items-center gap-1 px-3 py-2 bg-gray-100 text-gray-500 rounded-lg text-xs font-bold cursor-not-allowed" title="No split found">
                                            💳 Unable
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>

                                <!-- Attachment -->
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="text-xs text-gray-500">if any</span>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
            @else
                <!-- No Personal Settlement -->
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-8 text-center border-2 border-blue-200">
                    <p class="text-6xl mb-4">✨</p>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">You're All Settled!</h2>
                    <p class="text-gray-600">You have no outstanding balances in {{ $group->name }}.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Overall Settlement Matrix (visible to everyone) -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Overall Group Settlement</h2>
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gradient-to-r from-indigo-50 to-blue-50 border-b-2 border-gray-200">
                                <th class="px-4 sm:px-6 py-4 text-left font-bold text-gray-700">Person</th>
                                @foreach($group->members as $member)
                                    <th class="px-4 sm:px-6 py-4 text-center font-bold text-gray-700 whitespace-nowrap">
                                        {{ substr($member->name, 0, 3) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($group->members as $fromUser)
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-4 sm:px-6 py-4 font-semibold text-gray-900">
                                        {{ $fromUser->name }}
                                    </td>
                                    @foreach($group->members as $toUser)
                                        <td class="px-4 sm:px-6 py-4 text-center">
                                            @if($fromUser->id === $toUser->id)
                                                <span class="text-gray-400">—</span>
                                            @else
                                                @php
                                                    $amount = 0;
                                                    $color = 'gray'; // 'red' if fromUser owes toUser, 'green' if toUser owes fromUser

                                                    // Check if fromUser owes toUser (Row owes Column)
                                                    if (isset($overallSettlement[$fromUser->id]['owes'][$toUser->id])) {
                                                        $amount = $overallSettlement[$fromUser->id]['owes'][$toUser->id]['amount'];
                                                        $color = 'red'; // Row person owes column person
                                                    }
                                                    // Otherwise check if toUser owes fromUser (Column owes Row)
                                                    elseif (isset($overallSettlement[$toUser->id]['owes'][$fromUser->id])) {
                                                        $amount = $overallSettlement[$toUser->id]['owes'][$fromUser->id]['amount'];
                                                        $color = 'green'; // Column person owes row person
                                                    }
                                                @endphp

                                                @if($amount > 0)
                                                    @if($color === 'red')
                                                        <!-- Red: Row person owes column person -->
                                                        <span class="inline-block px-2 py-1 bg-red-100 text-red-700 rounded font-bold text-xs">
                                                            {{ number_format($amount, 2) }}
                                                        </span>
                                                    @elseif($color === 'green')
                                                        <!-- Green: Column person owes row person -->
                                                        <span class="inline-block px-2 py-1 bg-green-100 text-green-700 rounded font-bold text-xs">
                                                            {{ number_format($amount, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Transaction History Section -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">📜 All Group Transactions</h2>
        @if(count($transactionHistory) > 0)
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-50 to-pink-50 border-b-2 border-gray-200">
                                <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Date</th>
                                <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Type</th>
                                <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">From</th>
                                <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Description</th>
                                <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($transactionHistory as $transaction)
                                @php
                                    $isExpense = $transaction['type'] === 'expense';
                                @endphp
                                <tr class="hover:bg-gray-50 transition-all">
                                    <!-- Date -->
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="text-sm text-gray-600 font-medium">
                                            {{ $transaction['timestamp']->format('M d, Y') }}
                                        </span>
                                        <span class="text-xs text-gray-500 block">
                                            {{ $transaction['timestamp']->format('h:i A') }}
                                        </span>
                                    </td>

                                    <!-- Type Badge -->
                                    <td class="px-4 sm:px-6 py-4">
                                        @if($isExpense)
                                            <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                                                💰 Expense
                                            </span>
                                        @else
                                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                                ✓ Payment
                                            </span>
                                        @endif
                                    </td>

                                    <!-- From -->
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                                <span class="text-sm font-bold text-white">{{ strtoupper(substr($transaction['payer']->name, 0, 1)) }}</span>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ $transaction['payer']->name }}</span>
                                        </div>
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <p class="text-sm font-semibold text-gray-900">{{ $transaction['title'] }}</p>
                                            @if(!$isExpense && isset($transaction['recipient']))
                                                <p class="text-xs text-gray-600">→ to {{ $transaction['recipient']->name }}</p>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Amount -->
                                    <td class="px-4 sm:px-6 py-4">
                                        <p class="text-sm font-bold {{ $isExpense ? 'text-blue-600' : 'text-green-600' }}">
                                            ${{ number_format($transaction['amount'], 2) }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl shadow-lg p-8 text-center border-2 border-purple-200">
                <p class="text-6xl mb-4">📭</p>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">No Transactions Yet</h2>
                <p class="text-gray-600">No expenses or payments have been recorded in {{ $group->name }} yet.</p>
            </div>
        @endif
    </div>

    <!-- Advances Section -->
    @php
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with('senders', 'sentTo')
            ->latest()
            ->get();
    @endphp

    @if($advances->count() > 0)
        <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 sm:px-6 py-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span class="text-3xl">💰</span>
                    <span>Advances Paid</span>
                </h2>

                <div class="space-y-4">
                    @foreach($advances as $advance)
                        <div class="bg-white rounded-xl p-5 border-2 border-cyan-200 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-400 to-blue-400 flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-white">{{ strtoupper(substr($advance->sentTo->name, 0, 1)) }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-900">Advanced to {{ $advance->sentTo->name }}</p>
                                        <p class="text-sm text-gray-600">💰 ${{ number_format($advance->amount_per_person, 2) }} per person</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-cyan-600">${{ number_format($advance->amount_per_person * $advance->senders->count(), 2) }}</p>
                                    <p class="text-xs text-gray-500">{{ $advance->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-xs font-semibold text-gray-700 mb-2">Paid by:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($advance->senders as $sender)
                                        <span class="inline-block px-3 py-1 bg-cyan-100 text-cyan-700 rounded-full text-xs font-bold">
                                            {{ $sender->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            @if($advance->description)
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">📝 {{ $advance->description }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    @if($payments->count() === 0 && $advances->count() === 0)
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-lg p-8 text-center">
            <p class="text-4xl mb-4">📭</p>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">No Payments Yet</h2>
            <p class="text-gray-600">No payments have been marked as paid in this group yet.</p>
            <a href="{{ route('groups.dashboard', $group) }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold">
                Back to Dashboard
            </a>
        </div>
    @endif

    <!-- Mobile Floating Back Button -->
    <div class="fixed bottom-6 right-6 sm:hidden z-40">
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Back to Group">
            <span class="text-xl">←</span>
        </a>
    </div>
</div>

<script>
function toggleAttachments(paymentId) {
    const row = document.getElementById('attachments-' + paymentId);
    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
    } else {
        row.classList.add('hidden');
    }
}

function openImageModal(imageUrl, imageName) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const imageName2 = document.getElementById('imageName');

    modalImage.src = imageUrl;
    imageName2.textContent = imageName;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openPaymentModal(splitId, userName, amount) {
    document.getElementById('paymentUserName').textContent = userName;
    document.getElementById('paymentAmount').textContent = '$' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('paymentForm').action = '/splits/' + splitId + '/mark-paid';
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
}

function closePaymentModal(event) {
    if (!event || event.target.id === 'paymentModal') {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('flex');
    }
}

function openBreakdownModal(personName, itemData) {
    const modal = document.getElementById('breakdownModal');
    const title = document.getElementById('breakdownTitle');
    const details = document.getElementById('breakdownDetails');

    title.textContent = `Breakdown with ${personName}`;

    let html = '<div class="space-y-3">';

    // Calculate they spent for me vs I spent for them
    let theySpentForMe = 0;
    let iSpentForThem = 0;

    if (itemData.expenses && itemData.expenses.length > 0) {
        itemData.expenses.forEach(exp => {
            if (exp.type === 'you_owe') {
                // They paid it, you need to reimburse them
                theySpentForMe += parseFloat(exp.amount);
            } else {
                // You paid it for them
                iSpentForThem += parseFloat(exp.amount);
            }
        });
    }

    // Show "They spent for me"
    if (theySpentForMe > 0) {
        html += `<div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-700 font-semibold">${personName} spent for me</span>
                    <span class="font-bold text-red-600">$${theySpentForMe.toFixed(2)}</span>
                 </div>`;
    }

    // Show "I spent for them"
    if (iSpentForThem > 0) {
        html += `<div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-700 font-semibold">I spent for ${personName}</span>
                    <span class="font-bold text-green-600">-$${iSpentForThem.toFixed(2)}</span>
                 </div>`;
    }

    // Show advance if any
    const advance = itemData.advance || 0;
    if (advance > 0) {
        html += `<div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-700 font-semibold">💰 Advance paid</span>
                    <span class="font-bold text-blue-600">-$${parseFloat(advance).toFixed(2)}</span>
                 </div>`;
    }

    // Show final balance calculation
    const finalAmount = theySpentForMe - iSpentForThem - advance;
    const finalLabel = finalAmount > 0 ? `You owe ${personName}` : `${personName} owes you`;
    const finalColor = finalAmount > 0 ? 'text-red-600' : 'text-green-600';

    html += `<div class="flex flex-col items-start pt-3 border-t-2 border-gray-300">
                <span class="font-bold text-gray-900 mb-2">${finalLabel}</span>
                <span class="font-black text-4xl ${finalColor}">
                    $${Math.abs(finalAmount).toFixed(2)}
                </span>
             </div>`;

    html += '</div>';
    details.innerHTML = html;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeBreakdownModal(event) {
    if (!event || event.target.id === 'breakdownModal') {
        document.getElementById('breakdownModal').classList.add('hidden');
        document.getElementById('breakdownModal').classList.remove('flex');
    }
}

// Close modal when clicking outside the image
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeImageModal();
            }
        });
    }
});
</script>

<!-- Mark as Paid Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="closePaymentModal(event)">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b-2 border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Mark as Paid</h3>
        </div>

        <form id="paymentForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <div>
                <p class="text-sm text-gray-600">Amount to pay</p>
                <p id="paymentAmount" class="text-3xl font-bold text-blue-600">$0.00</p>
                <p class="text-sm text-gray-600 mt-1">to <span id="paymentUserName" class="font-bold">—</span></p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Paid Date (Optional)</label>
                <input type="date" name="paid_date" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Notes (Optional)</label>
                <textarea name="notes" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none" rows="3" placeholder="Add any notes..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Upload Receipt (Optional)</label>
                <input type="file" name="receipt" accept="image/png,image/jpeg" class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">📸 PNG or JPEG, max 5MB (auto-compressed to 50KB)</p>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all font-bold">
                    ✓ Mark as Paid
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Breakdown Modal -->
<div id="breakdownModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" onclick="closeBreakdownModal(event)">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="px-6 py-4 border-b-2 border-gray-200">
            <h3 id="breakdownTitle" class="text-xl font-bold text-gray-900">Breakdown Details</h3>
        </div>

        <div id="breakdownDetails" class="p-6">
            <!-- Details will be inserted here by JavaScript -->
        </div>

        <div class="px-6 py-4 border-t-2 border-gray-200 flex justify-end">
            <button onclick="closeBreakdownModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" onclick="closeImageModal()">
    <div class="relative max-w-4xl w-full mx-4" onclick="event.stopPropagation()">
        <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300 text-4xl font-bold">✕</button>
        <img id="modalImage" src="" alt="Attachment" class="w-full h-auto rounded-lg">
        <div class="mt-4 text-center">
            <p id="imageName" class="text-white font-semibold text-sm truncate"></p>
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

    <!-- Mobile Floating Action Buttons -->
    <x-group-fabs :group="$group" />
</div>

@endsection
