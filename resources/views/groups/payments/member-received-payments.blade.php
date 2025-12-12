@extends('layouts.app')

@section('title', $member->name . ' - Received Payments - ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <!-- Group Breadcrumb -->
    <x-group-breadcrumb :group="$group" />

    <!-- Header Section -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8 border-b border-gray-200">
        <div class="max-w-7xl mx-auto">
            <!-- Back Button & Title -->
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('groups.payments.history', $group) }}" class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                    ← Back to Settlement
                </a>
            </div>

            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl font-bold text-white">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                </div>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">{{ $member->name }}</h1>
                    <p class="text-gray-600 mt-1">Payment History</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- Payments Received By Member -->
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">💰 Payments Received</h2>
                <p class="text-sm text-gray-600 mb-4">Amounts {{ $member->name }} has received from others</p>

                @if($receivedPayments->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">From</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Notes</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php $totalReceived = 0; @endphp
                                    @foreach($receivedPayments as $payment)
                                        @php $totalReceived += $payment->amount; @endphp
                                        <tr class="hover:bg-green-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-red-400 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-bold text-white">{{ strtoupper(substr($payment->fromUser->name, 0, 1)) }}</span>
                                                    </div>
                                                    <span class="font-medium text-gray-900">{{ $payment->fromUser->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="font-bold text-green-600">${{ number_format($payment->amount, 2) }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-gray-600">{{ $payment->received_date->format('M d, Y') }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-gray-600 text-xs">{{ $payment->description ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <form action="{{ route('groups.received-payments.destroy', [$group, $payment]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-semibold">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-green-50 border-t-2 border-green-200">
                                        <td colspan="5" class="px-4 py-3">
                                            <div class="flex justify-end items-center gap-4">
                                                <span class="text-sm font-semibold text-gray-600">Total Received:</span>
                                                <span class="text-lg font-bold text-green-600">${{ number_format($totalReceived, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-8 text-center border-2 border-green-200">
                        <p class="text-gray-600">No payments received yet</p>
                    </div>
                @endif
            </div>

            <!-- Payments Sent By Member -->
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4">📤 Payments Sent</h2>
                <p class="text-sm text-gray-600 mb-4">Amounts {{ $member->name }} has sent to others</p>

                @if($sentPayments->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">To</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Notes</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @php $totalSent = 0; @endphp
                                    @foreach($sentPayments as $payment)
                                        @php $totalSent += $payment->amount; @endphp
                                        <tr class="hover:bg-blue-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-bold text-white">{{ strtoupper(substr($payment->toUser->name, 0, 1)) }}</span>
                                                    </div>
                                                    <span class="font-medium text-gray-900">{{ $payment->toUser->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="font-bold text-blue-600">${{ number_format($payment->amount, 2) }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-gray-600">{{ $payment->received_date->format('M d, Y') }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-gray-600 text-xs">{{ $payment->description ?? '—' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <form action="{{ route('groups.received-payments.destroy', [$group, $payment]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this payment record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs font-semibold">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-blue-50 border-t-2 border-blue-200">
                                        <td colspan="5" class="px-4 py-3">
                                            <div class="flex justify-end items-center gap-4">
                                                <span class="text-sm font-semibold text-gray-600">Total Sent:</span>
                                                <span class="text-lg font-bold text-blue-600">${{ number_format($totalSent, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg p-8 text-center border-2 border-blue-200">
                        <p class="text-gray-600">No payments sent yet</p>
                    </div>
                @endif
            </div>

            <!-- Summary -->
            @php
                $netAmount = $totalReceived - $totalSent;
            @endphp
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6 border-2 border-indigo-200">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Summary</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Received</p>
                        <p class="text-2xl font-bold text-green-600">${{ number_format($totalReceived, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Sent</p>
                        <p class="text-2xl font-bold text-blue-600">${{ number_format($totalSent, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Net Balance</p>
                        <p class="text-2xl font-bold {{ $netAmount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $netAmount >= 0 ? '+' : '−' }}${{ number_format(abs($netAmount), 2) }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
