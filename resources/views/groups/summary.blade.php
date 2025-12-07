@extends('layouts.app')

@section('title', 'Trip Summary - ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white">
    <!-- Header Section -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12 border-b border-gray-200">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-2">
                        📊 {{ $group->name }} - Trip Summary
                    </h1>
                    <p class="text-lg text-gray-600">
                        THE authoritative settlement record
                    </p>
                </div>
                <a href="{{ route('groups.dashboard', $group) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-7xl mx-auto space-y-8">

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Spent -->
                <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Total Spent</h3>
                    <p class="text-3xl font-bold text-blue-600">₹{{ number_format($totalAmount, 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $expenseCount }} expenses + {{ $advanceCount }} advances</p>
                </div>

                <!-- Members Count -->
                <div class="bg-white rounded-lg shadow-sm border border-purple-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Members</h3>
                    <p class="text-3xl font-bold text-purple-600">{{ $group->members->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">People in this trip</p>
                </div>

                <!-- Settled Payments -->
                <div class="bg-white rounded-lg shadow-sm border border-green-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Settled Payments</h3>
                    <p class="text-3xl font-bold text-green-600">{{ $paidPayments->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">Confirmed transactions</p>
                </div>

                <!-- Pending Settlements -->
                <div class="bg-white rounded-lg shadow-sm border border-orange-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-600 mb-2">Pending</h3>
                    <p class="text-3xl font-bold text-orange-600">{{ $settlement->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">Transactions needed</p>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
                <p class="text-blue-900 text-sm">
                    <strong>📌 ONE TRUTH:</strong> This page shows exactly who owes whom. All other views are just different ways to explore the same data. Trust this summary above all else.
                </p>
            </div>

            <!-- SETTLEMENT TABLE - THE FINAL WORD -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">💳 Who Pays Whom (Minimum Transactions)</h2>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    @if($settlement->isEmpty())
                        <div class="p-8 text-center">
                            <p class="text-gray-600 text-lg font-semibold">✅ Everyone is settled! No transactions needed.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                                        <th class="px-6 py-4 text-left font-bold">From</th>
                                        <th class="px-6 py-4 text-left font-bold">To</th>
                                        <th class="px-6 py-4 text-right font-bold">Amount</th>
                                        <th class="px-6 py-4 text-center font-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($settlement as $transaction)
                                    <tr class="hover:bg-gray-50 transition-colors" data-from="{{ $transaction['from']->id }}" data-to="{{ $transaction['to']->id }}">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="font-bold text-red-700">{{ strtoupper(substr($transaction['from']->name, 0, 1)) }}</span>
                                                </div>
                                                <span class="font-semibold text-gray-900" data-user-id="{{ $transaction['from']->id }}">{{ $transaction['from']->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="font-bold text-green-700">{{ strtoupper(substr($transaction['to']->name, 0, 1)) }}</span>
                                                </div>
                                                <span class="font-semibold text-gray-900" data-user-id="{{ $transaction['to']->id }}">{{ $transaction['to']->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-lg font-bold text-gray-900">₹{{ number_format($transaction['amount'], 0) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button
                                                type="button"
                                                onclick="openPaymentModal('{{ $transaction['from']->id }}', '{{ $transaction['to']->id }}', {{ $transaction['amount'] }})"
                                                class="inline-flex px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-800 hover:bg-orange-200 transition-colors cursor-pointer"
                                            >
                                                ⏳ Mark Paid
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Member Breakdown -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">👥 Member Summary</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($memberSummary as $memberId => $summary)
                    @php
                        $isPositive = $summary['balance'] > 0;
                        $borderColor = $isPositive ? 'border-green-200' : 'border-red-200';
                        $bgColor = $isPositive ? 'bg-green-50' : 'bg-red-50';
                        $textColor = $isPositive ? 'text-green-600' : 'text-red-600';
                    @endphp
                    <div class="bg-white rounded-lg shadow-sm border {{ $borderColor }} p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold text-lg">{{ strtoupper(substr($summary['user']->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ $summary['user']->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $summary['user']->email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Paid:</span>
                                <span class="font-bold text-gray-900">₹{{ number_format($summary['paid'], 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Should pay:</span>
                                <span class="font-bold text-gray-900">₹{{ number_format($summary['owes'], 0) }}</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between items-center">
                                <span class="font-semibold text-gray-900">Balance:</span>
                                <span class="text-lg font-bold {{ $textColor }}">
                                    {{ $isPositive ? '✅ +' : '❌ -' }}₹{{ number_format(abs($summary['balance']), 0) }}
                                </span>
                            </div>
                        </div>

                        @if($isPositive)
                            <p class="text-sm text-green-700 mt-4">💰 They should receive this amount</p>
                        @else
                            <p class="text-sm text-red-700 mt-4">💳 They should pay this amount</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Expense Breakdown -->
            @if($expenseCount > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">📋 All Transactions Included</h2>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                        @php
                            $expenses = $group->expenses()->where('split_type', '!=', 'itemwise')->with('payer')->latest()->get();
                        @endphp
                        @foreach($expenses as $expense)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $expense->title }}</h3>
                                    <p class="text-sm text-gray-600">Paid by {{ $expense->payer->name }}</p>
                                </div>
                                <span class="text-lg font-bold text-blue-600">₹{{ number_format($expense->amount, 0) }}</span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $expense->description }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ $expense->created_at->format('M d, Y') }}</p>
                        </div>
                        @endforeach

                        @php
                            $advances = \App\Models\Advance::where('group_id', $group->id)->with(['senders'])->latest()->get();
                        @endphp
                        @foreach($advances as $advance)
                        <div class="border border-amber-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-amber-50">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900">{{ $advance->description ?? 'Advance Payment' }}</h3>
                                    <p class="text-sm text-amber-800">Advanced to {{ $advance->sentTo->name }}</p>
                                </div>
                                <span class="text-lg font-bold text-amber-600">₹{{ number_format($advance->amount_per_person * count($advance->senders), 0) }}</span>
                            </div>
                            <p class="text-sm text-amber-700">Sent by: {{ $advance->senders->pluck('name')->implode(', ') }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ $advance->created_at->format('M d, Y') }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment History -->
            @if($paidPayments->count() > 0)
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">✅ Already Settled</h2>
                <div class="bg-white rounded-lg shadow-sm border border-green-200 overflow-hidden">
                    <div class="divide-y divide-gray-200">
                        @foreach($paidPayments as $payment)
                        <div class="px-6 py-4 hover:bg-green-50 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $payment->split->user->name }} paid {{ $payment->split->expense->payer->name }}
                                    </p>
                                    <p class="text-sm text-gray-600">{{ $payment->split->expense->title }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">✓ ₹{{ number_format($payment->split->share_amount, 0) }}</p>
                                    <p class="text-xs text-gray-500">{{ $payment->paid_date?->format('M d, Y') ?? $payment->updated_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Export Section -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-8 text-white text-center">
                <h3 class="text-2xl font-bold mb-2">📥 Export Summary</h3>
                <p class="mb-6">Save this trip summary as PDF for your records</p>
                <button onclick="window.print()" class="bg-white text-blue-600 font-bold px-6 py-3 rounded-lg hover:bg-gray-100 transition-colors inline-flex items-center gap-2">
                    <span>🖨️</span>
                    Download as PDF
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Payment Confirmation Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-4">
            <h2 class="text-2xl font-bold">✅ Confirm Payment</h2>
        </div>

        <!-- Body -->
        <div class="px-6 py-6 space-y-4">
            <p class="text-gray-700">
                <strong id="paymentText"></strong>
            </p>

            <!-- Payment Details -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Amount:</span>
                    <span class="font-bold text-gray-900">₹<span id="paymentAmount">0</span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="font-semibold text-gray-900">Cash / UPI / Bank</span>
                </div>
            </div>

            <!-- Photo Upload Section -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    📸 Receipt/Proof (Optional)
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-500 transition-colors cursor-pointer" id="photoDropzone">
                    <input type="file" id="paymentPhoto" accept="image/*" class="hidden" />
                    <div class="space-y-1">
                        <p class="text-2xl">📷</p>
                        <p class="text-sm font-semibold text-gray-700">Click to upload screenshot</p>
                        <p class="text-xs text-gray-500">UPI receipt, bank transfer confirmation, etc.</p>
                    </div>
                </div>
                <p id="photoFileName" class="text-sm text-green-600 mt-2 hidden">✓ Photo selected</p>
            </div>

            <!-- Confirmation Checkbox -->
            <label class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <input type="checkbox" id="paymentConfirm" class="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                <span class="text-sm text-gray-700">
                    I confirm that <strong id="paymentConfirmText"></strong> has been completed.
                </span>
            </label>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Notes (Optional)</label>
                <textarea
                    id="paymentNotes"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    placeholder="e.g., Paid via UPI on Jan 15"
                ></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 flex gap-3 rounded-b-lg border-t border-gray-200">
            <button
                type="button"
                onclick="closePaymentModal()"
                class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
            >
                Cancel
            </button>
            <button
                type="button"
                onclick="submitPayment()"
                id="submitPaymentBtn"
                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                disabled
            >
                ✓ Mark as Paid
            </button>
        </div>
    </div>
</div>

<!-- JavaScript for Payment Modal -->
<script>
let currentPayment = { fromId: null, toId: null, amount: null };

function openPaymentModal(fromId, toId, amount) {
    currentPayment = { fromId, toId, amount };

    // Get user names (from the page data)
    const fromName = document.querySelector(`[data-user-id="${fromId}"]`)?.textContent || 'Person A';
    const toName = document.querySelector(`[data-user-id="${toId}"]`)?.textContent || 'Person B';

    // Update modal with payment details
    document.getElementById('paymentText').textContent = `${fromName} pays ₹${amount.toLocaleString()} to ${toName}`;
    document.getElementById('paymentAmount').textContent = amount.toLocaleString();
    document.getElementById('paymentConfirmText').textContent = `${fromName} → ${toName} payment`;

    // Show modal
    document.getElementById('paymentModal').classList.remove('hidden');

    // Reset form
    document.getElementById('paymentConfirm').checked = false;
    document.getElementById('paymentPhoto').value = '';
    document.getElementById('paymentNotes').value = '';
    document.getElementById('photoFileName').classList.add('hidden');
    updateSubmitButton();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function updateSubmitButton() {
    const isConfirmed = document.getElementById('paymentConfirm').checked;
    document.getElementById('submitPaymentBtn').disabled = !isConfirmed;
}

// Photo upload handling
document.getElementById('photoDropzone').addEventListener('click', function() {
    document.getElementById('paymentPhoto').click();
});

document.getElementById('paymentPhoto').addEventListener('change', function(e) {
    if (this.files.length > 0) {
        document.getElementById('photoFileName').classList.remove('hidden');
    }
});

// Enable/disable submit button based on confirmation
document.getElementById('paymentConfirm').addEventListener('change', updateSubmitButton);

async function submitPayment() {
    const notes = document.getElementById('paymentNotes').value;
    const photoFile = document.getElementById('paymentPhoto').files[0];

    // Create FormData for file upload support
    const formData = new FormData();
    formData.append('from_user_id', currentPayment.fromId);
    formData.append('to_user_id', currentPayment.toId);
    formData.append('amount', currentPayment.amount);
    formData.append('notes', notes);
    if (photoFile) {
        formData.append('photo', photoFile);
    }

    try {
        // Send to backend
        const response = await fetch(`/groups/{{ $group->id }}/settlements/confirm`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        // Show success state
        const modal = document.getElementById('paymentModal');
        const successDiv = document.createElement('div');
        successDiv.className = 'bg-green-50 px-6 py-12 text-center rounded-lg';
        successDiv.innerHTML = `
            <div class="text-5xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-green-900 mb-2">Payment Recorded!</h3>
            <p class="text-green-700 mb-6">
                The settlement has been confirmed and saved with receipt.
            </p>
            <button onclick="location.reload()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                ✓ Refresh to See Updates
            </button>
        `;
        modal.innerHTML = '';
        modal.appendChild(successDiv);

    } catch (error) {
        console.error('Error submitting payment:', error);
        alert('Error recording payment. Please try again. Error: ' + error.message);
        closePaymentModal();
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
    }
});
</script>

<style>
    @media print {
        nav, button, .no-print, #paymentModal {
            display: none !important;
        }
        body {
            background: white;
        }
    }
</style>
@endsection
