@extends('layouts.app')

@section('title', $expense->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $expense->title }}</h1>
                <p class="text-gray-600 mt-1">{{ $group->name }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl sm:text-4xl font-bold text-blue-600">
                    {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($expense->amount) }}
                </div>
                <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold {{ $expense->status === 'fully_paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
                </span>
            </div>
        </div>

        @if($expense->description)
            <p class="text-gray-700 mb-4">{{ $expense->description }}</p>
        @endif

        <!-- Expense Details -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-200">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Paid By</p>
                <p class="text-lg font-semibold text-gray-900">{{ $expense->payer->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ $expense->date->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Created</p>
                <p class="text-lg font-semibold text-gray-900">{{ $expense->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Settlement Breakdown -->
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Settlement Breakdown</h2>

        @if(empty($settlement))
            <div class="text-center py-6">
                <p class="text-gray-600">The payer covered this entire expense (no one else owes money)</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($settlement as $item)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">
                                {{ $item['from']?->name ?? 'Unknown' }}
                                <span class="text-gray-600 font-normal">owes</span>
                                {{ $item['to']?->name ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600">
                                {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($item['amount']) }}
                            </div>
                            @if($item['paid'])
                                <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-semibold">✓ Paid</span>
                            @else
                                <span class="inline-block mt-1 px-2 py-0.5 bg-orange-100 text-orange-800 rounded text-xs font-semibold">⏳ Pending</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-semibold">Total to be settled:</span>
                    <span class="text-xl font-bold text-blue-600">
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency(collect($settlement)->sum('amount')) }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Extracted Line Items (OCR) -->
    @if($expense->items->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📦 Line Items (from Receipt)</h2>

            <div class="space-y-3">
                @foreach($expense->items as $item)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                Qty: {{ $item->quantity }} × {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($item->unit_price) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600">
                                {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($item->total_price) }}
                            </div>
                            @if($item->user_id)
                                <p class="text-xs text-gray-600 mt-2 font-semibold">
                                    👤 {{ $item->assignedTo->name ?? 'Unknown' }}
                                </p>
                            @else
                                <p class="text-xs text-orange-600 mt-2 font-semibold">
                                    ⚠️ Not assigned
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Expense Splits -->
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Who Pays What</h2>

        <div class="space-y-2">
            @forelse($expense->splits as $split)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-900">{{ $split->getMemberName() }}</span>
                        @if($split->contact_id)
                            <span class="text-xs px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded">Contact</span>
                        @endif
                    </div>
                    <span class="font-semibold text-gray-900">
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($split->share_amount) }}
                    </span>
                </div>
            @empty
                <p class="text-gray-600 text-center py-4">No splits defined</p>
            @endforelse
        </div>
    </div>

    <!-- Comments Section -->
    @if($expense->comments->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Comments</h2>

            <div class="space-y-4">
                @foreach($expense->comments as $comment)
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start mb-1">
                            <p class="font-semibold text-gray-900">{{ $comment->user->name }}</p>
                            <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-700">{{ $comment->comment }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Attachments Section -->
    @if($expense->attachments->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <span>📎</span>
                <span>Attachments ({{ $expense->attachments->count() }})</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($expense->attachments as $attachment)
                    <div class="border-2 border-blue-200 rounded-lg p-4 hover:shadow-md transition-all">
                        <div class="flex flex-col">
                            <div class="flex-shrink-0 mb-3">
                                @if(str_contains($attachment->mime_type, 'image'))
                                    <img
                                        src="{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}"
                                        alt="{{ $attachment->file_name }}"
                                        class="h-24 w-full object-cover rounded cursor-pointer hover:opacity-75 transition-opacity"
                                        onclick="openImageModal('{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}', '{{ addslashes($attachment->file_name) }}')"
                                    />
                                @else
                                    <div class="h-24 w-full bg-gray-100 rounded flex items-center justify-center">
                                        <span class="text-4xl">📄</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 text-sm break-words truncate">{{ $attachment->file_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $attachment->file_size_kb }} KB</p>
                                <p class="text-xs text-gray-500">{{ $attachment->created_at->format('M d, Y') }}</p>
                                <a
                                    href="{{ route('attachments.download', ['attachment' => $attachment->id]) }}"
                                    target="_blank"
                                    class="inline-block mt-3 px-3 py-1 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700 transition-colors"
                                >
                                    Download →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    @if($expense->payer_id === auth()->id())
        <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row gap-3">
                <a
                    href="{{ route('groups.expenses.edit', ['group' => $group, 'expense' => $expense]) }}"
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-center"
                >
                    ✏️ Edit Expense
                </a>
                <form
                    action="{{ route('groups.expenses.destroy', ['group' => $group, 'expense' => $expense]) }}"
                    method="POST"
                    class="flex-1"
                    onsubmit="return confirm('Are you sure you want to delete this expense?');"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold"
                    >
                        🗑️ Delete Expense
                    </button>
                </form>
                <a
                    href="{{ route('groups.show', $group) }}"
                    class="hidden sm:flex flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold items-center justify-center"
                >
                    ← Back to Group
                </a>
            </div>
        </div>
    @else
        <div class="hidden sm:text-center sm:block">
            <a
                href="{{ route('groups.show', $group) }}"
                class="inline-block px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold"
            >
                ← Back to Group
            </a>
        </div>
    @endif

    <!-- Mobile Floating Back Button -->
    <div class="fixed bottom-6 right-6 sm:hidden z-40">
        <a
            href="{{ route('groups.show', $group) }}"
            class="flex items-center justify-center w-14 h-14 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition-all transform hover:scale-110 font-bold shadow-lg"
            title="Back to Group"
        >
            <span class="text-xl">←</span>
        </a>
    </div>
</div>

<script nonce="{{ request()->attributes->get('nonce', '') }}">
function openImageModal(imageUrl, imageName) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const imageNameEl = document.getElementById('imageName');

    modalImage.src = imageUrl;
    imageNameEl.textContent = imageName;
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

    // Handle data-open-image-modal buttons
    document.querySelectorAll('[data-open-image-modal="true"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const imageUrl = this.dataset.imageUrl;
            const imageName = this.dataset.imageName;
            openImageModal(imageUrl, imageName);
        });
    });

    // Handle data-close-image-modal buttons
    document.querySelectorAll('[data-close-image-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeImageModal);
    });
});
</script>

<!-- Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50" data-close-image-modal="true">
    <div class="relative max-w-4xl w-full mx-4" data-stop-propagation="true">
        <button data-close-image-modal="true" class="absolute -top-10 right-0 text-white hover:text-gray-300 text-4xl font-bold">✕</button>
        <img id="modalImage" src="" alt="Attachment" class="w-full h-auto rounded-lg">
        <div class="mt-4 text-center">
            <p id="imageName" class="text-white font-semibold text-sm truncate"></p>
        </div>
    </div>
</div>

@endsection
