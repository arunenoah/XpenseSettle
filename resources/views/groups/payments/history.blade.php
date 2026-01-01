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
            <div class="hidden sm:flex justify-end gap-3">
                <a href="{{ route('groups.payments.export-member-settlements-pdf', $group) }}"
                   download
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 transition-all font-semibold text-sm shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Member Settlements
                </a>
                <a href="{{ route('groups.payments.export-pdf', $group) }}"
                   download
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-all font-semibold text-sm shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Summary
                </a>
            </div>

    <!-- Settlement Suggestions Section -->
    @if(false && !empty($settlementSuggestions))
    <div class="my-8 hidden">
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
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">📊 Overall Group Settlement</h2>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="sticky left-0 z-10 bg-gray-50 px-3 sm:px-4 py-3 text-left font-bold text-gray-600 text-xs uppercase shadow-sm">Person</th>
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
                                <td class="sticky left-0 z-10 bg-white px-3 sm:px-4 py-3 font-semibold text-gray-900 text-sm whitespace-nowrap shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $fromData['user']->name }}</span>
                                        @if($fromData['is_contact'])
                                            <span class="text-xs px-1 py-0.5 bg-cyan-100 text-cyan-700 rounded">✨</span>
                                        @endif
                                    </div>
                                </td>
                                @foreach($overallSettlement as $toMemberId => $toData)
                                    <td class="px-2 sm:px-3 py-3 text-center relative group">
                                        @if($fromMemberId === $toMemberId)
                                            <span class="text-gray-300">—</span>
                                        @else
                                            @php
                                                $amount = 0;
                                                $color = 'gray';
                                                $breakdown = '';
                                                $itemData = null;
                                                $personName = '';

                                                if (isset($fromData['owes'][$toMemberId])) {
                                                    $amount = $fromData['owes'][$toMemberId]['amount'];
                                                    $color = 'red';
                                                    $breakdown = $fromData['owes'][$toMemberId]['breakdown'] ?? '';
                                                    $itemData = $fromData['owes'][$toMemberId];
                                                    $personName = $toData['user']->name;
                                                }
                                                elseif (isset($toData['owes'][$fromMemberId])) {
                                                    $amount = $toData['owes'][$fromMemberId]['amount'];
                                                    $color = 'green';
                                                    $breakdown = $toData['owes'][$fromMemberId]['breakdown'] ?? '';
                                                    $itemData = $toData['owes'][$fromMemberId];
                                                    $personName = $fromData['user']->name;
                                                }
                                            @endphp

                                            @if($amount > 0)
                                                @if($color === 'red')
                                                    <button class="settlement-btn inline-block px-1.5 py-0.5 bg-red-100 text-red-700 rounded font-bold text-xs whitespace-nowrap cursor-pointer hover:bg-red-200 border-0" 
                                                        data-breakdown="{{ strlen($breakdown) > 0 ? base64_encode($breakdown) : base64_encode('No breakdown data') }}"
                                                        data-person-name="{{ $personName }}"
                                                        data-item-json="{{ base64_encode(json_encode($itemData)) }}"
                                                        style="background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; border: none; cursor: pointer;">
                                                        ${{ formatCurrency($amount) }}
                                                    </button>
                                                @elseif($color === 'green')
                                                    <button class="settlement-btn inline-block px-1.5 py-0.5 bg-green-100 text-green-700 rounded font-bold text-xs whitespace-nowrap cursor-pointer hover:bg-green-200 border-0" 
                                                        data-breakdown="{{ strlen($breakdown) > 0 ? base64_encode($breakdown) : base64_encode('No breakdown data') }}"
                                                        data-person-name="{{ $personName }}"
                                                        data-item-json="{{ base64_encode(json_encode($itemData)) }}"
                                                        style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; border: none; cursor: pointer;">
                                                        ${{ formatCurrency($amount) }}
                                                    </button>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            @else
                                                {{-- Fully settled - check if we have settled data --}}
                                                @php
                                                    $settledData = null;
                                                    $personName = '';
                                                    if (isset($fromData['settled'][$toMemberId])) {
                                                        $settledData = $fromData['settled'][$toMemberId];
                                                        // The other person in this settlement is $toData
                                                        $personName = $toData['user']->name;
                                                    } elseif (isset($toData['settled'][$fromMemberId])) {
                                                        $settledData = $toData['settled'][$fromMemberId];
                                                        // The other person in this settlement is $fromData
                                                        $personName = $fromData['user']->name;
                                                    }
                                                @endphp
                                                
                                                @if($settledData)
                                                    {{-- Show green checkmark with transaction history --}}
                                                    <button class="settlement-btn inline-flex items-center justify-center w-7 h-7 bg-green-100 rounded-full cursor-pointer hover:bg-green-200 border-0 transition-all" 
                                                        data-breakdown="{{ base64_encode($settledData['breakdown'] ?? 'Fully settled') }}"
                                                        data-person-name="{{ $personName }}"
                                                        data-item-json="{{ base64_encode(json_encode($settledData)) }}"
                                                        title="Fully settled - click to view history">
                                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </button>
                                                @else
                                                    {{-- No transaction history - just show dash --}}
                                                    <span class="text-gray-300">—</span>
                                                @endif
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
                                                    
    <!-- Category Breakdown Section -->
    @if(count($categoryBreakdown) > 0)
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">📊 Expenses by Category</h2>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-3 sm:px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Category</th>
                            <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Count</th>
                            <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Total Amount</th>
                            <th class="px-3 sm:px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">% of Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $grandTotal = collect($categoryBreakdown)->sum('total');
                        @endphp
                        @foreach($categoryBreakdown as $catData)
                            @php
                                $percentage = $grandTotal > 0 ? ($catData['total'] / $grandTotal * 100) : 0;
                                // Get icon from category constants
                                $categoryIcons = [
                                    'Accommodation' => '🏨',
                                    'Food & Dining' => '🍽️',
                                    'Groceries' => '🛒',
                                    'Transport' => '✈️',
                                    'Activities' => '🎫',
                                    'Shopping' => '🛍️',
                                    'Utilities & Services' => '⚙️',
                                    'Fees & Charges' => '💳',
                                    'Other' => '📝',
                                ];
                                $icon = $categoryIcons[$catData['category']] ?? '📝';
                            @endphp
                            <tr class="hover:bg-blue-50 transition-colors">
                                <td class="px-3 sm:px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $icon }}</span>
                                        <span class="font-medium text-gray-900">{{ $catData['category'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-right">
                                    <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">
                                        {{ $catData['count'] }}
                                    </span>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-right">
                                    <span class="font-bold text-gray-900">${{ formatCurrency($catData['total']) }}</span>
                                </td>
                                <td class="px-3 sm:px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500" style="width: {{ $percentage }}%;"></div>
                                        </div>
                                        <span class="font-semibold text-blue-600 min-w-max">{{ number_format($percentage, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Transaction History Section -->
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">📜 Transaction History</h2>
        @if(count($transactionHistory) > 0)
            
            <!-- Mobile Cards (hidden on sm+) -->
            <div class="sm:hidden space-y-3 mb-4">
                @foreach($transactionHistory as $transaction)
                    @php
                        $isExpense = $transaction['type'] === 'expense';
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br {{ $isExpense ? 'from-blue-400 to-blue-500' : 'from-green-400 to-green-500' }} flex items-center justify-center">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr($transaction['payer']->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $transaction['payer']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $transaction['timestamp']->format('M d, h:i A') }}</p>
                                </div>
                            </div>
                            <p class="text-lg font-black {{ $isExpense ? 'text-blue-600' : 'text-green-600' }}">
                                ${{ formatCurrency($transaction['amount']) }}
                            </p>
                        </div>
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                {{ $transaction['title'] }}
                                @if($isExpense && isset($transaction['has_attachments']) && $transaction['has_attachments'])
                                    <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded">
                                        📎 {{ $transaction['attachments']->count() }}
                                    </span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                @if($isExpense)
                                    💰 Added expense
                                @else
                                    @if(isset($transaction['recipient']))
                                        ✓ Payment to {{ $transaction['recipient']->name }}
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Desktop Table (hidden on mobile) -->
            <div class="hidden sm:block bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
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
                                    $transactionId = $isExpense ? $transaction['expense_id'] : $transaction['payment_id'];
                                    $transactionType = $isExpense ? 'expense' : 'payment';
                                @endphp
                                <tr class="hover:bg-blue-50 transition-colors cursor-pointer" onclick="handleTransactionRowClick('{{ $transactionType }}', {{ $transactionId }}, {{ $isExpense ? 'true' : 'false' }}, '{{ $isExpense ? route('groups.expenses.show', ['group' => $group, 'expense' => $transaction['expense_id']]) : '#' }}')">
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
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-medium text-gray-900">{{ $transaction['title'] }}</span>
                                                    @if(isset($transaction['has_attachments']) && $transaction['has_attachments'])
                                                        <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded">
                                                            📎 {{ $transaction['attachments']->count() }}
                                                        </span>
                                                    @endif
                                                </div>
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
                                            ${{ formatCurrency($transaction['amount']) }}
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
                                        <p class="text-sm text-gray-600">💰 ${{ formatCurrency($advance->amount_per_person) }} per person</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-cyan-600">${{ formatCurrency($advance->amount_per_person * $advance->senders->count()) }}</p>
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

    <!-- Received Payments Section -->
    @php
        $receivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->with('fromUser', 'toUser')
            ->latest()
            ->get();
    @endphp

    @if($receivedPayments->count() > 0)
        <div class="bg-gradient-to-br from-teal-50 via-emerald-50 to-green-50 rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 sm:px-6 py-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span class="text-3xl">💸</span>
                    <span>Received Payments</span>
                </h2>

                <div class="space-y-4">
                    @foreach($receivedPayments as $payment)
                        @php
                            $isFromMe = $payment->from_user_id === auth()->id();
                            $isToMe = $payment->to_user_id === auth()->id();
                        @endphp
                        <div class="bg-white rounded-xl p-5 border-2 {{ $isToMe ? 'border-teal-200' : 'border-purple-200' }} shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br {{ $isToMe ? 'from-teal-400 to-emerald-400' : 'from-purple-400 to-pink-400' }} flex items-center justify-center flex-shrink-0">
                                        <span class="text-sm font-bold text-white">{{ $isToMe ? '💰' : '💸' }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-900">
                                            {{ $payment->fromUser->name }} → {{ $payment->toUser->name }}
                                        </p>
                                        <p class="text-sm {{ $isToMe ? 'text-teal-600' : 'text-purple-600' }} font-semibold">
                                            {{ $isToMe ? 'You received' : 'You sent' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold {{ $isToMe ? 'text-teal-600' : 'text-purple-600' }}">${{ formatCurrency($payment->amount) }}</p>
                                    <p class="text-xs text-gray-500">{{ ($payment->payment_date ?? $payment->created_at)->format('M d, Y') }}</p>
                                </div>
                            </div>

                            @if($payment->notes)
                                <div class="pt-3 border-t border-gray-200">
                                    <p class="text-sm text-gray-600">💬 {{ $payment->notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($payments->count() === 0 && $advances->count() === 0 && $receivedPayments->count() === 0)
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-lg p-8 text-center">
            <p class="text-4xl mb-4">📭</p>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">No Payments Yet</h2>
            <p class="text-gray-600">No payments have been marked as paid in this group yet.</p>
            <a href="{{ route('groups.dashboard', $group) }}" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold">
                Back to Dashboard
            </a>
        </div>
    @endif

    <!-- Transaction Details Modal -->
    <div id="transactionDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto" onclick="closeTransactionDetailsModal(event)">
        <div class="bg-white rounded-2xl max-w-2xl w-full mx-4 shadow-2xl my-8" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                <h2 class="text-xl font-bold text-gray-900">Transaction Details</h2>
                <button onclick="closeTransactionDetailsModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">
                    ✕
                </button>
            </div>

            <!-- Modal Loader -->
            <div id="transactionLoader" class="px-6 py-8 flex items-center justify-center">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    <p class="text-gray-600">Loading transaction details...</p>
                </div>
            </div>

            <!-- Modal Content -->
            <div id="transactionDetailsContent" class="px-6 py-6"></div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 rounded-b-2xl flex justify-end">
                <button onclick="closeTransactionDetailsModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Floating Back Button -->
    <div class="fixed bottom-6 right-6 sm:hidden z-40">
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Back to Group">
            <span class="text-xl">←</span>
        </a>
    </div>
</div>

<script nonce="{{ request()->attributes->get('nonce', '') }}">
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

// Handle transaction row click - navigate to expense or show payment details
function handleTransactionRowClick(type, id, isExpense, expenseUrl) {
    if (isExpense) {
        // For expenses, navigate directly to the expense details page
        window.location.href = expenseUrl;
    } else {
        // For payments, show the transaction details modal
        openTransactionDetailsModal(type, id);
    }
}

// Transaction Details Modal
function openTransactionDetailsModal(type, id) {
    const modal = document.getElementById('transactionDetailsModal');
    const loader = document.getElementById('transactionLoader');
    const content = document.getElementById('transactionDetailsContent');

    // Show loader
    loader.classList.remove('hidden');
    content.innerHTML = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Fetch transaction details
    fetch(`/groups/{{ $group->id }}/transaction-details/${type}/${id}`)
        .then(response => response.json())
        .then(data => {
            loader.classList.add('hidden');
            if (data.success) {
                renderTransactionDetails(data.transaction, type);
            } else {
                content.innerHTML = '<p class="text-red-600">Error loading transaction details</p>';
            }
        })
        .catch(error => {
            loader.classList.add('hidden');
            content.innerHTML = '<p class="text-red-600">Error loading transaction details</p>';
            console.error('Error:', error);
        });
}

function renderTransactionDetails(transaction, type) {
    const content = document.getElementById('transactionDetailsContent');

    if (type === 'expense') {
        content.innerHTML = `
            <div class="space-y-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="text-lg font-bold text-gray-900">${transaction.title}</h3>
                    <p class="text-sm text-gray-600 mt-1">Added by ${transaction.payer_name}</p>
                    <p class="text-2xl font-bold text-blue-600 mt-3">$${parseFloat(transaction.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>

                ${transaction.description ? `
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Description</p>
                    <p class="text-gray-900">${transaction.description}</p>
                </div>
                ` : ''}

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Date</p>
                    <p class="text-gray-900">${transaction.date}</p>
                </div>

                ${transaction.attachments && transaction.attachments.length > 0 ? `
                <div class="border-t pt-4 mt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Attachments (${transaction.attachments.length})</p>
                    <div class="space-y-2">
                        ${transaction.attachments.map(att => `
                            <a href="${att.url}" target="_blank" class="block p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">📄</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-blue-600 truncate">${att.name}</p>
                                        <p class="text-xs text-gray-500">${att.size}</p>
                                    </div>
                                    <span class="text-xl">🔗</span>
                                </div>
                            </a>
                        `).join('')}
                    </div>
                </div>
                ` : '<p class="text-sm text-gray-500 text-center py-4">No attachments</p>'}
            </div>
        `;
    } else if (type === 'payment') {
        content.innerHTML = `
            <div class="space-y-4">
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Payment Details</p>
                    <p class="text-lg font-bold text-gray-900 mt-1">${transaction.payer_name} → ${transaction.recipient_name}</p>
                    <p class="text-2xl font-bold text-green-600 mt-3">$${parseFloat(transaction.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>

                ${transaction.notes ? `
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Notes</p>
                    <p class="text-gray-900">${transaction.notes}</p>
                </div>
                ` : ''}

                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Date</p>
                    <p class="text-gray-900">${transaction.date}</p>
                </div>

                ${transaction.receipt ? `
                <div class="border-t pt-4 mt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Receipt</p>
                    <a href="${transaction.receipt.url}" target="_blank" class="block p-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📷</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-blue-600 truncate">${transaction.receipt.name}</p>
                                <p class="text-xs text-gray-500">${transaction.receipt.size}</p>
                            </div>
                            <span class="text-xl">🔗</span>
                        </div>
                    </a>
                </div>
                ` : '<p class="text-sm text-gray-500 text-center py-4">No receipt attached</p>'}
            </div>
        `;
    }
}

function closeTransactionDetailsModal(event) {
    if (!event || event.target.id === 'transactionDetailsModal') {
        const modal = document.getElementById('transactionDetailsModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
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

    console.log('Opening breakdown for:', personName);
    console.log('Item data:', itemData);
    console.log('Expenses:', itemData.expenses);

    title.textContent = `Settlement: ${personName}`;

    let html = '<div class="space-y-4 text-sm">';

    // Check if fully settled (amount is 0)
    const isFullySettled = itemData.amount === 0;

    // Group expenses by payer
    let theyPaidExpenses = [];
    let iPaidExpenses = [];
    let theySpentForMe = 0;
    let iSpentForThem = 0;

    // Separate regular expenses from adjustments
    let adjustments = [];

    if (itemData.expenses && itemData.expenses.length > 0) {
        itemData.expenses.forEach(exp => {
            // Check if this is an adjustment (advance or payment)
            if (exp.title === 'Advance paid' || exp.title === 'Advance received' ||
                exp.title === 'Payment received' || exp.title === 'Payment sent' ||
                exp.title === 'Payment Sent' || exp.title === 'Payment Received') {
                adjustments.push(exp);
            } else if (exp.type === 'you_owe') {
                // They paid it, you need to reimburse them (user is participant, other person is payer)
                theyPaidExpenses.push(exp);
                theySpentForMe += parseFloat(exp.amount);
            } else if (exp.type === 'they_owe') {
                // You paid it for them (user is payer, other person is participant)
                iPaidExpenses.push(exp);
                iSpentForThem += parseFloat(exp.amount);
            }
        });
    }

    // Always show expense details for transparency, whether settled or not
    // Get the correct other person's name
    const otherPersonName = itemData.user && itemData.user.name ? itemData.user.name : personName;

    // Show expenses paid by them (otherPersonName) - these are expenses where they are the payer and you are a participant
    if (theyPaidExpenses.length > 0) {
        html += `<div class="bg-red-50 p-3 rounded-lg border border-red-200">
                    <p class="font-bold text-gray-900 mb-2">Expenses paid by ${otherPersonName} (you participated):</p>
                    <ul class="space-y-1 ml-2">`;

        theyPaidExpenses.forEach(exp => {
            html += `<li class="flex justify-between">
                        <span class="text-gray-700">• ${exp.title}:</span>
                        <span class="font-semibold text-gray-900">$${parseFloat(exp.amount).toFixed(2)}</span>
                     </li>`;
        });

        html += `</ul>
                 <div class="flex justify-between items-center mt-2 pt-2 border-t border-red-300">
                    <span class="font-bold text-gray-900">Subtotal:</span>
                    <span class="font-bold text-red-600">$${theySpentForMe.toFixed(2)}</span>
                 </div>
              </div>`;
    }

    // Show expenses paid by me (currentUser) - these are expenses where you are the payer and they are a participant
    if (iPaidExpenses.length > 0) {
        html += `<div class="bg-green-50 p-3 rounded-lg border border-green-200">
                    <p class="font-bold text-gray-900 mb-2">Expenses you paid for ${otherPersonName}:</p>
                    <ul class="space-y-1 ml-2">`;

        iPaidExpenses.forEach(exp => {
            html += `<li class="flex justify-between">
                        <span class="text-gray-700">• ${exp.title}:</span>
                        <span class="font-semibold text-gray-900">$${parseFloat(exp.amount).toFixed(2)}</span>
                     </li>`;
        });

        html += `</ul>
                 <div class="flex justify-between items-center mt-2 pt-2 border-t border-green-300">
                    <span class="font-bold text-gray-900">Subtotal:</span>
                    <span class="font-bold text-green-600">$${iSpentForThem.toFixed(2)}</span>
                 </div>
              </div>`;
    }

    // Show adjustments section only if there are adjustments
    if (adjustments.length > 0) {
        html += `<div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <p class="font-bold text-gray-900 mb-2">Adjustments:</p>
                    <ul class="space-y-1 ml-2">`;

        adjustments.forEach(adj => {
            const isNegative = adj.title.includes('paid') || adj.title.includes('received');
            const color = isNegative ? 'text-blue-600' : 'text-gray-900';
            html += `<li class="flex justify-between">
                        <span class="text-gray-700">• ${adj.title}:</span>
                        <span class="font-semibold ${color}">-$${parseFloat(adj.amount).toFixed(2)}</span>
                     </li>`;
        });

        html += `</ul>
              </div>`;
    }

    // Use the pre-calculated amount from backend (already correct in the table)
    const finalAmount = itemData.amount || 0;
    
    if (isFullySettled) {
        // Show fully settled banner
        html += `<div class="flex flex-col items-center pt-3 border-t-2 border-green-300 bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="font-bold text-gray-700 mb-1 text-sm">✨ Fully Settled!</span>
                    <span class="font-black text-3xl text-green-600">$0.00</span>
                    <span class="text-xs text-gray-500 mt-1">No outstanding balance</span>
                 </div>`;
    } else {
        // Show regular balance
        const netAmount = itemData.net_amount || 0;
        const finalColor = netAmount > 0 ? 'text-red-600' : 'text-green-600';
        // Use the user from itemData if available, otherwise fall back to personName
        const otherPersonName = itemData.user && itemData.user.name ? itemData.user.name : personName;
        const finalText = netAmount > 0 ? `(${currentUserName} owes ${otherPersonName})` : `(${otherPersonName} owes ${currentUserName})`;
        
        html += `<div class="flex flex-col items-center pt-3 border-t-2 border-gray-300 bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-lg">
                    <span class="font-bold text-gray-700 mb-1 text-xs">${finalText}</span>
                    <span class="font-black text-3xl ${finalColor}">
                        $${parseFloat(finalAmount).toFixed(2)}
                    </span>
                 </div>`;
    }

    html += '</div>';
    details.innerHTML = html;

    // Show modal consistently and prevent background scroll
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    console.log('Modal displayed via openBreakdownModal');
}

function closeBreakdownModal() {
    const modal = document.getElementById('breakdownModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.style.display = '';
    document.body.style.overflow = 'auto';
    console.log('✅ Breakdown modal closed');
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
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" data-close-modal="true" data-modal-func="closePaymentModal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4" data-stop-propagation="true">
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
                <button type="button" data-close-button="true" data-modal-func="closePaymentModal" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-bold">
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
<div id="breakdownModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-4 overflow-y-auto" data-close-modal="true" data-modal-func="closeBreakdownModal">
    <div class="bg-white rounded-2xl shadow-2xl w-full sm:max-w-2xl mx-2 sm:mx-4 flex flex-col max-h-[calc(100vh-32px)]" data-stop-propagation="true">
        <div class="px-3 sm:px-6 py-3 border-b border-gray-200 bg-white flex-shrink-0">
            <h3 id="breakdownTitle" class="text-base sm:text-lg font-bold text-gray-900">Breakdown Details</h3>
        </div>

        <div id="breakdownDetails" class="p-3 sm:p-6 overflow-y-scroll flex-grow min-h-0">
            <!-- Details will be inserted here by JavaScript -->
        </div>

        <div class="px-3 sm:px-6 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 flex-shrink-0">
            <button onclick="closeBreakdownModal()" data-close-button="true" data-modal-func="closeBreakdownModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-bold text-xs sm:text-sm">
                Close
            </button>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar styling for breakdown modal */
    #breakdownDetails::-webkit-scrollbar {
        width: 10px;
    }
    #breakdownDetails::-webkit-scrollbar-track {
        background: #f0f0f0;
    }
    #breakdownDetails::-webkit-scrollbar-thumb {
        background: #0066cc;
        border-radius: 5px;
    }
    #breakdownDetails::-webkit-scrollbar-thumb:hover {
        background: #0052a3;
    }
</style>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" data-close-image-modal="true">
    <div class="relative max-w-4xl w-full mx-4" data-stop-propagation="true">
        <button data-close-image-modal="true" class="absolute -top-10 right-0 text-white hover:text-gray-300 text-4xl font-bold">✕</button>
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
    <div id="expensesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto" data-close-modal="true" data-modal-func="closeExpensesModal">
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 my-8" data-stop-propagation="true">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-black text-gray-900">📜 Expenses</h3>
                <button data-close-button="true" data-modal-func="closeExpensesModal" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
            </div>

            @if($group->expenses->count() > 0)
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($group->expenses as $expense)
                        <a href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}" class="block p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 truncate">{{ $expense->title }}</h4>
                                    <p class="text-sm text-gray-600 mt-1">💰 ${{ formatCurrency($expense->amount) }}</p>
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

    <script nonce="{{ request()->attributes->get('nonce', '') }}">
    // Modal fix v3.0 - Removed problematic click handler entirely - 2025-12-15 14:22
    // Store current authenticated user's name for settlement popup
    const currentUserName = '{{ auth()->user()->name ?? "You" }}';
    console.log('🔧 Settlement modal script v3.0 loaded');
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

    // Settlement breakdown modal functionality
    function initSettlementBreakdown() {
        const buttons = document.querySelectorAll('.settlement-btn');
        const modal = document.getElementById('breakdownModal');
        const breakdownContent = document.getElementById('breakdownDetails');

        if (!modal) {
            console.log('Settlement breakdown modal not found');
            return;
        }

        console.log('Modal and content element found: ' + (breakdownContent ? 'yes' : 'no'));

        buttons.forEach((btn, idx) => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation(); // Completely stop event propagation
                
                console.log('Button clicked, event stopped');
                
                // Get structured data
                const personName = this.getAttribute('data-person-name');
                const encodedItemData = this.getAttribute('data-item-json');
                
                if (encodedItemData && personName) {
                    try {
                        const itemDataJson = atob(encodedItemData);
                        const itemData = JSON.parse(itemDataJson);
                        console.log('Opening breakdown for:', personName);
                        console.log('Item data:', itemData);
                        
                        // Use setTimeout to ensure modal opens after any close handlers finish
                        setTimeout(() => {
                            openBreakdownModal(personName, itemData);
                        }, 10);
                        return;
                    } catch (error) {
                        console.error('Error parsing item data:', error);
                    }
                }
                
                // Fallback to old text-based breakdown
                const encodedBreakdown = this.getAttribute('data-breakdown');
                if (encodedBreakdown) {
                    try {
                        const breakdown = atob(encodedBreakdown);
                        console.log('Decoded breakdown: ' + breakdown);

                        if (breakdownContent) {
                            console.log('Setting content');
                            // Convert newlines to <br> tags and escape HTML
                            let htmlContent = breakdown
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/\n/g, '<br>');

                            console.log('HTML content: ' + htmlContent);

                            // Set the inner HTML
                            breakdownContent.innerHTML = htmlContent;

                            console.log('Content set via innerHTML');
                            console.log('Element innerHTML: ' + breakdownContent.innerHTML);
                        }

                        // Show modal with inline styles to override Tailwind
                        modal.classList.remove('hidden');
                        modal.style.display = 'flex';
                        modal.style.visibility = 'visible';
                        modal.style.opacity = '1';

                        // Debug: Check the content element's actual computed styles
                        setTimeout(() => {
                            const computedColor = window.getComputedStyle(breakdownContent).color;
                            const computedDisplay = window.getComputedStyle(breakdownContent).display;
                            const computedVisibility = window.getComputedStyle(breakdownContent).visibility;
                            const computedOpacity = window.getComputedStyle(breakdownContent).opacity;

                            console.log('After modal display:');
                            console.log('Content color: ' + computedColor);
                            console.log('Content display: ' + computedDisplay);
                            console.log('Content visibility: ' + computedVisibility);
                            console.log('Content opacity: ' + computedOpacity);
                            console.log('Content textContent: ' + breakdownContent.textContent.substring(0, 30));
                        }, 100);

                        console.log('Modal displayed with inline styles');
                    } catch(e) {
                        console.log('Error: ' + e);
                    }
                }
            });
        });

        console.log('Settlement breakdown initialization complete');
    }

    // Run immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSettlementBreakdown);
    } else {
        initSettlementBreakdown();
    }
    </script>

    <!-- Received Payment Modal -->
    <div id="receivedPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" style="display: none; visibility: hidden;">
        <div class="bg-white rounded-lg shadow-2xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900">Mark Payment Received</h2>
                <button type="button" data-close-received-modal class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>

            <p class="text-gray-600 mb-4">Recording payment from <strong id="modalMemberName"></strong></p>

            @if ($errors->has('amount'))
                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-red-700 font-semibold">{{ $errors->first('amount') }}</p>
                </div>
            @endif

            <form id="receivedPaymentForm" action="{{ route('groups.received-payments.store', $group) }}" method="POST" class="space-y-4">
                @csrf

                <input type="hidden" id="memberIdInput" name="from_user_id">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Amount Received ($)</label>
                    <input type="number" id="amountInput" name="amount" step="0.01" min="0.01" required
                           class="w-full px-4 py-2 border {{ $errors->has('amount') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                           placeholder="0.00"
                           value="{{ old('amount') }}">
                    @if ($errors->has('amount'))
                        <p class="text-xs text-red-600 mt-1">{{ $errors->first('amount') }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Date Received</label>
                    <input type="date" name="received_date" required value="{{ date('Y-m-d') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200"
                              placeholder="e.g., Cash payment for hotel share..."></textarea>
                </div>

                <div class="flex gap-2 pt-4">
                    <button type="button" data-close-received-modal
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        ✓ Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Received Payment Modal Functions
    function closeReceivedPaymentModal() {
        const modal = document.getElementById('receivedPaymentModal');
        if (modal) {
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.classList.add('hidden');
            document.getElementById('receivedPaymentForm').reset();
        }
    }

    // Initialize received payment modal handlers when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Handle "Mark Paid" button clicks
        const markPaidButtons = document.querySelectorAll('.mark-paid-btn-history');
        markPaidButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = document.getElementById('receivedPaymentModal');
                if (!modal) {
                    console.error('Modal not found!');
                    return;
                }

                const memberName = this.getAttribute('data-member-name');
                const memberId = this.getAttribute('data-member-id');
                const suggestedAmount = this.getAttribute('data-suggested-amount');

                console.log('Opening modal for:', memberName, suggestedAmount);
                document.getElementById('modalMemberName').textContent = memberName;
                document.getElementById('memberIdInput').value = memberId;

                // Set suggested amount if provided
                if (suggestedAmount) {
                    document.getElementById('amountInput').value = suggestedAmount;
                }

                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.classList.remove('hidden');
                console.log('Modal opened');
            });
        });

        // Handle close buttons
        const modal = document.getElementById('receivedPaymentModal');
        if (modal) {
            const closeButtons = modal.querySelectorAll('[data-close-received-modal]');
            closeButtons.forEach(button => {
                button.addEventListener('click', closeReceivedPaymentModal);
            });

            // Close modal when clicking on the dark overlay
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeReceivedPaymentModal();
                }
            });
        }
    });
    </script>

    <!-- Breakdown Modal -->
    <div id="breakdownModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-900">Settlement Breakdown</h2>
                <button data-close-breakdown type="button" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="breakdownContent" style="max-height: 300px; overflow-y: auto; min-height: 80px; color: #000; background-color: #f9fafb; padding: 16px; font-family: 'Courier New', monospace; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; line-height: 1.5;"></div>
            <div class="mt-6">
                <button data-close-breakdown type="button" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Close</button>
            </div>
        </div>
    </div>

    <!-- Mobile Floating Action Buttons -->
    <x-group-fabs :group="$group" :showPdfExport="true" />
</div>

@endsection
