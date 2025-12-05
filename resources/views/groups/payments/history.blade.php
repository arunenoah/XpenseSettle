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
                Payment History
            </h1>
            <p class="mt-2 text-gray-600">{{ $group->name }}</p>
        </div>
    </div>

    @if($payments->count() > 0)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-50 to-purple-50 border-b-2 border-gray-200">
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Expense</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Person</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Amount</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Date</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-sm font-bold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payments as $payment)
                            <tr class="hover:bg-gray-50 transition-all">
                                <!-- Expense Title -->
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex flex-col">
                                        <p class="font-semibold text-gray-900">{{ $payment->split->expense->title }}</p>
                                        <p class="text-xs text-gray-500">ID: {{ $payment->id }}</p>
                                    </div>
                                </td>

                                <!-- Person -->
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-white">{{ strtoupper(substr($payment->split->user->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $payment->split->user->name }}</span>
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="px-4 sm:px-6 py-4">
                                    <p class="font-bold text-gray-900">${{ number_format($payment->split->share_amount, 2) }}</p>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($payment->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full">⏳ Pending</span>
                                    @elseif($payment->status === 'paid')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">✓ Paid</span>
                                    @elseif($payment->status === 'approved')
                                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">✓ Approved</span>
                                    @elseif($payment->status === 'rejected')
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">✗ Rejected</span>
                                    @endif
                                </td>

                                <!-- Date -->
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex flex-col">
                                        @if($payment->paid_date)
                                            <p class="text-sm text-gray-900 font-medium">{{ $payment->paid_date->format('M d, Y') }}</p>
                                        @else
                                            <p class="text-sm text-gray-500 italic">—</p>
                                        @endif
                                        <p class="text-xs text-gray-500">{{ $payment->created_at->diffForHumans() }}</p>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 sm:px-6 py-4">
                                    @if($payment->attachments->count() > 0)
                                        <button onclick="toggleAttachments({{ $payment->id }})" class="inline-flex items-center gap-1 px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-all text-xs font-bold">
                                            📎 {{ $payment->attachments->count() }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">No attachments</span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Attachments Row -->
                            @if($payment->attachments->count() > 0)
                                <tr id="attachments-{{ $payment->id }}" class="hidden bg-blue-50">
                                    <td colspan="6" class="px-4 sm:px-6 py-4">
                                        <div class="space-y-2">
                                            <h4 class="font-bold text-gray-900 mb-3">📎 Attachments:</h4>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($payment->attachments as $attachment)
                                                    <div class="bg-white rounded-lg p-3 border-2 border-blue-200">
                                                        <div class="flex items-start gap-2">
                                                            @if(str_contains($attachment->mime_type, 'image'))
                                                                <img src="{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}" alt="Attachment" class="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity" onclick="openImageModal('{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}', '{{ addslashes($attachment->file_name) }}')">
                                                            @else
                                                                <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center">
                                                                    <span class="text-2xl">📄</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex-1">
                                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $attachment->file_name }}</p>
                                                                <p class="text-xs text-gray-500">{{ $attachment->file_size_kb }} KB</p>
                                                                <p class="text-xs text-gray-500">{{ $attachment->created_at->format('M d, Y') }}</p>
                                                                <a href="{{ route('attachments.download', ['attachment' => $attachment->id]) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 font-bold mt-1 inline-block">
                                                                    Download →
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center">
            {{ $payments->links() }}
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
