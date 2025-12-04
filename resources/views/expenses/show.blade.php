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
                    {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($expense->amount, 2) }}
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
                                {{ $item['from']->name }}
                                <span class="text-gray-600 font-normal">owes</span>
                                {{ $item['to']->name }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-blue-600">
                                {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($item['amount'], 2) }}
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
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format(collect($settlement)->sum('amount'), 2) }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Expense Splits -->
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Who Pays What</h2>

        <div class="space-y-2">
            @forelse($expense->splits as $split)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span class="text-gray-900">{{ $split->user->name }}</span>
                    <span class="font-semibold text-gray-900">
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($split->share_amount, 2) }}
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
            <h2 class="text-xl font-bold text-gray-900 mb-4">Attachments</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($expense->attachments as $attachment)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                @if(str_starts_with($attachment->mime_type, 'image/'))
                                    <img
                                        src="{{ asset('storage/' . $attachment->file_path) }}"
                                        alt="{{ $attachment->original_name }}"
                                        class="h-16 w-16 object-cover rounded"
                                    />
                                @else
                                    <div class="h-16 w-16 bg-gray-100 rounded flex items-center justify-center">
                                        <span class="text-2xl">📄</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 text-sm break-words">{{ $attachment->original_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $attachment->file_size_kb }} KB</p>
                                <a
                                    href="{{ asset('storage/' . $attachment->file_path) }}"
                                    download
                                    class="inline-block mt-2 px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold hover:bg-blue-200"
                                >
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    @if($canManage)
        <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row gap-3">
                <a
                    href="{{ route('expenses.edit', ['group' => $group, 'expense' => $expense]) }}"
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-center"
                >
                    Edit Expense
                </a>
                <form
                    action="{{ route('expenses.destroy', ['group' => $group, 'expense' => $expense]) }}"
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
                        Delete Expense
                    </button>
                </form>
                <a
                    href="{{ route('groups.show', $group) }}"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold text-center"
                >
                    Back to Group
                </a>
            </div>
        </div>
    @else
        <div class="text-center">
            <a
                href="{{ route('groups.show', $group) }}"
                class="inline-block px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold"
            >
                Back to Group
            </a>
        </div>
    @endif
</div>
@endsection
