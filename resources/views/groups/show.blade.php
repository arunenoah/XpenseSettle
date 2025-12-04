@extends('layouts.app')

@section('title', $group->name)

@section('content')
<div class="space-y-6">
    <!-- Redirect to Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('groups.dashboard', $group) }}" class="text-blue-600 hover:text-blue-700 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l-2 1m0 0a2 2 0 100 4h.01m-2-4a2 2 0 110 4M9 9a6 6 0 100 12h.01M9 9a6 6 0 110 12M9 9a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
                View Group Dashboard
            </a>
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mt-2">{{ $group->name }}</h1>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('groups.show', $group) }}" class="py-2 px-4 border-b-2 border-blue-600 text-blue-600 font-semibold">Expenses</a>
            <a href="{{ route('groups.dashboard', $group) }}" class="py-2 px-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300">Dashboard</a>
        </div>
    </div>

    <!-- Add Expense Button -->
    <div class="flex justify-end">
        <a
            href="{{ route('groups.expenses.create', $group) }}"
            class="inline-flex items-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Expense
        </a>
    </div>

    <!-- Recent Expenses Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-4">Recent Expenses</h2>

        @if($expenses->count())
            <div class="space-y-3">
                @foreach($expenses as $expense)
                    <a href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}" class="block p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 truncate hover:text-blue-600">{{ $expense->title }}</h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">{{ $expense->payer->name }}</span>
                                    <span class="text-gray-400">•</span>
                                    <span>{{ $expense->splits()->count() }} people</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $expense->date->format('M d, Y') }}</p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="font-bold text-lg text-gray-900">{{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($expense->amount, 2) }}</p>
                                <span class="inline-block mt-1 px-2 py-1 {{ $expense->status === 'fully_paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }} text-xs font-semibold rounded">
                                    {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
                                </span>
                            </div>
                        </div>

                        @if($expense->description)
                            <p class="text-sm text-gray-600 mt-2">{{ $expense->description }}</p>
                        @endif

                        <!-- Split Breakdown -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <details class="cursor-pointer" @click.stop>
                                <summary class="text-sm text-gray-600 font-medium hover:text-gray-900">
                                    <span>Split Details ({{ $expense->splits()->count() }} people)</span>
                                </summary>
                                <div class="mt-2 space-y-1 pl-4">
                                    @foreach($expense->splits as $split)
                                        <div class="flex justify-between items-center py-1">
                                            <span class="text-sm text-gray-600">{{ $split->user->name }}</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}{{ number_format($split->share_amount, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $expenses->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <p class="text-gray-600 font-medium">No expenses yet</p>
                <p class="text-sm text-gray-500 mt-1">Add the first expense to get started</p>
                <a href="{{ route('groups.expenses.create', $group) }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Expense
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
