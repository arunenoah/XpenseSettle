<!-- Expense Header -->
<div class="border-b border-gray-200 pb-4 mb-4">
    <div class="flex items-start justify-between gap-3 mb-2">
        <div>
            <h3 class="text-xl font-bold text-gray-900">{{ $expense->title }}</h3>
            <p class="text-sm text-gray-600 mt-1">{{ $group->name }}</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-blue-600">
                {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}{{ formatCurrency($expense->amount) }}
            </p>
            <span class="inline-block mt-1 px-2 py-0.5 rounded text-xs font-semibold {{ $expense->status === 'fully_paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
            </span>
        </div>
    </div>

    @if($expense->description)
        <p class="text-sm text-gray-600 italic">{{ $expense->description }}</p>
    @endif
</div>

<!-- Expense Details -->
<div class="grid grid-cols-3 gap-2 mb-4 text-sm">
    <div class="bg-gray-50 p-2 rounded">
        <p class="text-xs text-gray-500 font-semibold">Paid By</p>
        <p class="text-gray-900 font-semibold truncate">{{ $expense->payer->name }}</p>
    </div>
    <div class="bg-gray-50 p-2 rounded">
        <p class="text-xs text-gray-500 font-semibold">Date</p>
        <p class="text-gray-900 font-semibold">{{ $expense->date->format('M d') }}</p>
    </div>
    <div class="bg-gray-50 p-2 rounded">
        <p class="text-xs text-gray-500 font-semibold">Type</p>
        <p class="text-gray-900 font-semibold">{{ ucfirst(str_replace('_', ' ', $expense->split_type)) }}</p>
    </div>
</div>

<!-- Settlement Breakdown -->
<div class="mb-4 border-b border-gray-200 pb-4">
    <h4 class="font-semibold text-gray-900 text-sm mb-2">Settlement</h4>
    @if(empty($settlement))
        <p class="text-xs text-gray-600">Payer covered entire expense</p>
    @else
        <div class="space-y-1">
            @foreach($settlement as $item)
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-xs">
                    <span class="text-gray-700">{{ $item['from']?->name ?? 'Unknown' }} → {{ $item['to']?->name ?? 'Unknown' }}</span>
                    <div class="text-right">
                        <span class="font-semibold text-blue-600">{{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ formatCurrency($item['amount']) }}</span>
                        @if($item['paid'])
                            <span class="ml-1 text-green-600 font-semibold">✓</span>
                        @else
                            <span class="ml-1 text-orange-600 font-semibold">⏳</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Expense Splits -->
<div class="mb-4 border-b border-gray-200 pb-4">
    <h4 class="font-semibold text-gray-900 text-sm mb-2">Split Among</h4>
    <div class="space-y-1">
        @forelse($expense->splits as $split)
            <div class="flex items-center justify-between p-2 bg-gray-50 rounded text-xs">
                <span class="text-gray-700 flex items-center gap-2">
                    {{ $split->getMemberName() }}
                    @if($split->contact_id)
                        <span class="px-1.5 py-0.5 bg-cyan-100 text-cyan-800 rounded text-xs">Contact</span>
                    @endif
                </span>
                <span class="font-semibold text-gray-900">{{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ formatCurrency($split->share_amount) }}</span>
            </div>
        @empty
            <p class="text-gray-600 text-center py-2">No splits defined</p>
        @endforelse
    </div>
</div>

<!-- Attachments Section -->
@if($expense->attachments->count() > 0)
    <div class="mb-4 border-b border-gray-200 pb-4">
        <h4 class="font-semibold text-gray-900 text-sm mb-2">📎 Attachments ({{ $expense->attachments->count() }})</h4>
        <div class="grid grid-cols-2 gap-2">
            @foreach($expense->attachments as $attachment)
                <div class="border border-gray-200 rounded p-1 text-center">
                    @if(str_contains($attachment->mime_type, 'image'))
                        <img
                            src="{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}"
                            alt="{{ $attachment->file_name }}"
                            class="h-12 w-full object-cover rounded cursor-pointer hover:opacity-75 transition-opacity"
                            onclick="openImageModal('{{ route('attachments.show', ['attachment' => $attachment->id, 'inline' => true]) }}', '{{ addslashes($attachment->file_name) }}')"
                        />
                    @else
                        <div class="h-12 w-full bg-gray-100 rounded flex items-center justify-center">
                            <span class="text-xl">📄</span>
                        </div>
                    @endif
                    <p class="text-xs text-gray-600 mt-1 truncate">{{ $attachment->file_name }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Comments Section -->
@if($expense->comments->count() > 0)
    <div class="mb-4">
        <h4 class="font-semibold text-gray-900 text-sm mb-2">Comments</h4>
        <div class="space-y-2 max-h-20 overflow-y-auto">
            @foreach($expense->comments as $comment)
                <div class="border-l-2 border-blue-400 pl-2 py-1">
                    <div class="flex justify-between items-start gap-1">
                        <p class="text-xs font-semibold text-gray-900">{{ $comment->user->name }}</p>
                        <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-gray-700 mt-0.5">{{ $comment->comment }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Action Buttons -->
<div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
    @if($expense->payer_id === auth()->id())
        <a
            href="{{ route('groups.expenses.edit', ['group' => $group, 'expense' => $expense]) }}"
            class="flex-1 px-3 py-2 bg-blue-600 text-white rounded text-xs font-semibold hover:bg-blue-700 transition-colors text-center"
            target="_blank"
        >
            ✏️ Edit
        </a>
    @endif
    <a
        href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}"
        class="flex-1 px-3 py-2 bg-gray-600 text-white rounded text-xs font-semibold hover:bg-gray-700 transition-colors text-center"
        target="_blank"
    >
        📖 Full View
    </a>
</div>
