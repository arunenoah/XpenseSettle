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
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- Export Button Section (Desktop Only) -->
            <div class="hidden sm:flex justify-end">
                <a href="{{ route('groups.payments.export-pdf', $group) }}"
                   download
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-all font-semibold text-sm shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                </a>
            </div>

            <!-- Personal Settlement Section -->
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">Your Settlement</h2>
                @if(count($personalSettlement) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-3 sm:px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Person</th>
                                    <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Owed</th>
                                    <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Balance</th>
                                    <th class="px-3 sm:px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Status</th>
                                    <th class="px-3 sm:px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($personalSettlement as $item)
                                    @php
                                        $isOwed = $item['net_amount'] > 0;
                                        $finalAmount = abs($item['net_amount']);
                                        $shouldShow = true;

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
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <!-- Person -->
                                        <td class="px-3 sm:px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-white">{{ strtoupper(substr($item['user']->name, 0, 1)) }}</span>
                                                </div>
                                                <span class="font-medium text-gray-900 truncate">{{ $item['user']->name }}</span>
                                            </div>
                                        </td>

                                        <!-- They spent for me -->
                                        <td class="px-3 sm:px-4 py-3 text-right">
                                            @if($theySpentForMe > 0)
                                                <span class="font-bold text-red-600">${{ number_format($theySpentForMe, 2) }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>

                                        <!-- Balance -->
                                        <td class="px-3 sm:px-4 py-3 text-right">
                                            <p class="font-bold text-lg {{ $isOwed ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $isOwed ? '$' : '-$' }}{{ number_format(abs($finalAmount), 2) }}
                                            </p>
                                        </td>

                                        <!-- Status -->
                                        <td class="px-3 sm:px-4 py-3 text-center">
                                            @if($isOwed)
                                                <span class="inline-block px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">Owing</span>
                                            @else
                                                <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">Owed</span>
                                            @endif
                                        </td>

                                        <!-- Action -->
                                        <td class="px-3 sm:px-4 py-3 text-center">
                                            @if($isOwed && isset($item['split_ids']) && count($item['split_ids']) > 0)
                                                <button onclick="openPaymentModal('{{ $item['split_ids'][0] }}', '{{ addslashes($item['user']->name) }}', '{{ $finalAmount }}')" class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold hover:bg-blue-200 transition-all">
                                                    Pay
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
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

    <!-- Settlement Suggestions Section -->
    @if(count($settlementSuggestions) > 0)
    <div class="my-8">
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl shadow-lg overflow-hidden border-2 border-amber-200">
            <div class="px-4 sm:px-6 py-6 sm:py-8">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-6">
                    <span class="text-4xl">💡</span>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Quick Settlement Plan</h2>
                        <p class="text-sm text-gray-600 mt-1">Optimized payment instructions to settle all balances</p>
                    </div>
                </div>

                <!-- Suggestions List -->
                <div class="space-y-3">
                    @foreach($settlementSuggestions as $index => $suggestion)
                    <div class="bg-white rounded-xl p-4 sm:p-5 border-2 border-amber-100 hover:border-amber-300 hover:shadow-md transition-all">
                        <div class="flex items-center gap-4 flex-wrap sm:flex-nowrap">
                            <!-- Step Number -->
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-400 flex items-center justify-center">
                                <span class="text-lg font-bold text-white">{{ $index + 1 }}</span>
                            </div>

                            <!-- Payment Flow -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <!-- From Person -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-400 to-red-500 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold text-white">{{ strtoupper(substr($suggestion['from'], 0, 1)) }}</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 truncate">{{ $suggestion['from'] }}</span>
                                    </div>

                                    <!-- Arrow -->
                                    <span class="text-gray-400 flex-shrink-0 hidden sm:inline">→</span>

                                    <!-- Amount Badge -->
                                    <div class="px-3 py-1 bg-gradient-to-r from-amber-200 to-orange-200 rounded-full">
                                        <span class="font-black text-lg text-amber-900">${{ $suggestion['formatted_amount'] }}</span>
                                    </div>

                                    <!-- Arrow (Mobile) -->
                                    <span class="text-gray-400 flex-shrink-0 sm:hidden">→</span>

                                    <!-- To Person -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-green-500 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold text-white">{{ strtoupper(substr($suggestion['to'], 0, 1)) }}</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 truncate">{{ $suggestion['to'] }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Copy Button -->
                            <button onclick="copySuggestion('{{ $suggestion['from'] }} → {{ $suggestion['to'] }} ${{ $suggestion['formatted_amount'] }}')" class="flex-shrink-0 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all text-xs font-bold">
                                📋 Copy
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Info Note -->
                <div class="mt-6 p-4 bg-amber-100 rounded-lg border-l-4 border-amber-500">
                    <p class="text-sm text-amber-900">
                        <strong>💬 Tip:</strong> These are optimized payment instructions. Share these with your group members to settle all balances in the minimum number of transactions.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Overall Settlement Matrix (visible to everyone) -->
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">Overall Group Settlement</h2>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 sm:px-4 py-3 text-left font-bold text-gray-600 text-xs uppercase">Person</th>
                            @foreach($overallSettlement as $memberId => $data)
                                <th class="px-2 sm:px-3 py-3 text-center font-bold text-gray-700 whitespace-nowrap text-xs sm:text-sm">
                                    <div>{{ substr($data['user']->name, 0, 2) }}</div>
                                    @if($data['is_contact'])
                                        <div class="text-xs text-cyan-600">✨</div>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($overallSettlement as $fromMemberId => $fromData)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 sm:px-4 py-3 font-semibold text-gray-900 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $fromData['user']->name }}</span>
                                        @if($fromData['is_contact'])
                                            <span class="text-xs px-1 py-0.5 bg-cyan-100 text-cyan-700 rounded">✨</span>
                                        @endif
                                    </div>
                                </td>
                                @foreach($overallSettlement as $toMemberId => $toData)
                                    <td class="px-2 sm:px-3 py-3 text-center">
                                        @if($fromMemberId === $toMemberId)
                                            <span class="text-gray-300">—</span>
                                        @else
                                            @php
                                                $amount = 0;
                                                $color = 'gray';

                                                if (isset($fromData['owes'][$toMemberId])) {
                                                    $amount = $fromData['owes'][$toMemberId]['amount'];
                                                    $color = 'red';
                                                }
                                                elseif (isset($toData['owes'][$fromMemberId])) {
                                                    $amount = $toData['owes'][$fromMemberId]['amount'];
                                                    $color = 'green';
                                                }
                                            @endphp

                                            @if($amount > 0)
                                                @if($color === 'red')
                                                    <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-700 rounded font-bold text-xs whitespace-nowrap">
                                                        ${{ number_format($amount, 2) }}
                                                    </span>
                                                @elseif($color === 'green')
                                                    <span class="inline-block px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-bold text-xs whitespace-nowrap">
                                                        ${{ number_format($amount, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            @else
                                                <span class="text-gray-300">—</span>
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

    <!-- Transaction History Section -->
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">📜 Transaction History</h2>
        @if(count($transactionHistory) > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-3 sm:px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                <th class="px-3 sm:px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Person</th>
                                <th class="px-3 sm:px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Details</th>
                                <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transactionHistory as $transaction)
                                @php
                                    $isExpense = $transaction['type'] === 'expense';
                                @endphp
                                <tr class="hover:bg-blue-50 transition-colors">
                                    <!-- Date -->
                                    <td class="px-3 sm:px-4 py-3">
                                        <span class="text-xs text-gray-600 font-medium">
                                            {{ $transaction['timestamp']->format('M d') }}
                                        </span>
                                        <span class="text-xs text-gray-500 block">
                                            {{ $transaction['timestamp']->format('h:i A') }}
                                        </span>
                                    </td>

                                    <!-- Person -->
                                    <td class="px-3 sm:px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-bold text-white">{{ strtoupper(substr($transaction['payer']->name, 0, 1)) }}</span>
                                            </div>
                                            <span class="text-xs font-medium text-gray-900 truncate">{{ $transaction['payer']->name }}</span>
                                        </div>
                                    </td>

                                    <!-- Details -->
                                    <td class="px-3 sm:px-4 py-3">
                                        <div class="flex flex-col gap-0.5">
                                            @if($isExpense)
                                                <span class="text-xs font-medium text-gray-900">{{ $transaction['title'] }}</span>
                                                <span class="text-xs text-gray-500">💰 Added expense</span>
                                            @else
                                                <span class="text-xs font-medium text-gray-900">{{ $transaction['title'] }}</span>
                                                @if(isset($transaction['recipient']))
                                                    <span class="text-xs text-gray-500">✓ Payment to {{ $transaction['recipient']->name }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Amount -->
                                    <td class="px-3 sm:px-4 py-3 text-right">
                                        <p class="text-xs font-bold {{ $isExpense ? 'text-blue-600' : 'text-green-600' }}">
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
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg shadow-sm p-8 text-center border-2 border-purple-200">
                <p class="text-4xl mb-3">📭</p>
                <h3 class="text-lg font-bold text-gray-900 mb-1">No Transactions Yet</h3>
                <p class="text-sm text-gray-600">No expenses or payments have been recorded.</p>
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

function copySuggestion(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success feedback
        const button = event.target.closest('button');
        const originalText = button.textContent;
        button.textContent = '✓ Copied!';
        button.classList.add('bg-green-100', 'text-green-700');
        button.classList.remove('bg-blue-100', 'text-blue-700');

        setTimeout(function() {
            button.textContent = originalText;
            button.classList.remove('bg-green-100', 'text-green-700');
            button.classList.add('bg-blue-100', 'text-blue-700');
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy:', err);
    });
}
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
    <x-group-fabs :group="$group" :showPdfExport="true" />
</div>

@endsection
