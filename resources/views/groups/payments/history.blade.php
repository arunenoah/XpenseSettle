@extends('layouts.app')

@section('title', 'Payment History - ' . $group->name)

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('groups.dashboard', $group) }}" class="text-blue-600 hover:text-blue-700 font-semibold mb-2 inline-block">
                ← Back to Group
            </a>
            <h1 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Settlement Summary
            </h1>
            <p class="mt-2 text-gray-600">{{ $group->name }} • {{ auth()->user()->name }}</p>
        </div>
    </div>


    <!-- Settlement Breakdown Table -->
    @if(count($settlement) > 0)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-50 to-purple-50 border-b-2 border-gray-200">
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Expense</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Bill by</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">They spent for me</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">I spent for them</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Advance sent</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Final Balance</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Details</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Action</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Attachment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($settlement as $item)
                            @php
                                $isOwed = $item['net_amount'] > 0;
                                $finalAmount = $isOwed ? ($item['amount'] - $item['advance']) : $item['amount'];
                                // Show row if user owes money OR if there's an advance involved
                                $shouldShow = $isOwed || $item['advance'] > 0;

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

                                <!-- Advance sent -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($item['advance'] > 0)
                                        <span class="inline-block px-2 py-1 bg-cyan-100 text-cyan-700 rounded text-xs font-bold">
                                            💰 ${{ number_format($item['advance'], 2) }}
                                        </span>
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
                                            😬 Pending
                                        </span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">
                                            ✓ Advance paid
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
                                    @if($isOwed)
                                        <button onclick="openPaymentModal('{{ $item['user']->id }}', '{{ addslashes($item['user']->name) }}', '{{ $finalAmount }}')" class="inline-flex items-center gap-1 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all text-xs font-bold">
                                            💳 Mark as paid
                                        </button>
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
        <!-- No Settlement -->
        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-8 text-center border-2 border-blue-200">
            <p class="text-6xl mb-4">✨</p>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">All Settled!</h2>
            <p class="text-gray-600">You have no outstanding balances in {{ $group->name }}.</p>
        </div>
    @endif

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

function openPaymentModal(userId, userName, amount) {
    document.getElementById('paymentUserName').textContent = userName;
    document.getElementById('paymentAmount').textContent = '$' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('paymentForm').action = '/splits/' + userId + '/mark-paid';
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
    if (itemData.advance > 0) {
        html += `<div class="flex justify-between items-center pb-3 border-b border-gray-200">
                    <span class="text-gray-700 font-semibold">💰 Advance paid</span>
                    <span class="font-bold text-blue-600">-$${parseFloat(itemData.advance).toFixed(2)}</span>
                 </div>`;
    }

    // Show final balance calculation
    const finalAmount = theySpentForMe - iSpentForThem - itemData.advance;
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
</div>

@endsection
