@extends('layouts.app')

@section('title', 'Add Expense to ' . $group->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Add Expense</h1>
        <p class="text-gray-600 mb-6">Create a new expense for <strong>{{ $group->name }}</strong></p>

        <form action="{{ route('groups.expenses.store', $group) }}" method="POST" class="space-y-6">
            @csrf

            <!-- Expense Title -->
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Expense Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('title') ? 'border-red-500' : '' }}"
                    placeholder="e.g., Dinner, Movie tickets, Gas"
                    required
                    autofocus
                />
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('description') ? 'border-red-500' : '' }}"
                    placeholder="Optional: Add any details about this expense"
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
                <div class="relative">
                    <span class="absolute left-4 top-3 sm:top-4 text-gray-600 font-semibold">
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}
                    </span>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        value="{{ old('amount') }}"
                        step="0.01"
                        min="0.01"
                        class="w-full pl-8 pr-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('amount') ? 'border-red-500' : '' }}"
                        placeholder="0.00"
                        required
                    />
                </div>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date -->
            <div>
                <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="{{ old('date', now()->toDateString()) }}"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('date') ? 'border-red-500' : '' }}"
                    required
                />
                @error('date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Split Type -->
            <div>
                <label for="split_type" class="block text-sm font-semibold text-gray-700 mb-2">How to Split?</label>
                <select
                    id="split_type"
                    name="split_type"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('split_type') ? 'border-red-500' : '' }}"
                    onchange="toggleCustomSplits()"
                    required
                >
                    <option value="equal" {{ old('split_type', 'equal') === 'equal' ? 'selected' : '' }}>Equal Split (divide evenly)</option>
                    <option value="custom" {{ old('split_type') === 'custom' ? 'selected' : '' }}>Custom Split (specify amounts)</option>
                </select>
                @error('split_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Custom Splits Section -->
            <div id="custom-splits" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold text-blue-900 mb-3">Specify how much each person owes</h3>
                    <p class="text-sm text-blue-800 mb-4">Total must equal <strong id="total-display">0.00</strong> {{ $group->currency }}</p>

                    <div class="space-y-3">
                        @foreach($members as $member)
                            <div class="flex items-center gap-3">
                                <label for="split-{{ $member->id }}" class="flex-1 text-sm font-medium text-gray-700">
                                    {{ $member->name }}
                                </label>
                                <div class="relative flex-1">
                                    <span class="absolute right-3 top-2 sm:top-3 text-gray-600 text-sm">
                                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}
                                    </span>
                                    <input
                                        type="number"
                                        id="split-{{ $member->id }}"
                                        name="splits[{{ $member->id }}]"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('splits.' . $member->id, 0) }}"
                                        class="w-full px-3 pr-8 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        onchange="updateTotal()"
                                    />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-blue-200">
                        <div class="flex justify-between">
                            <span class="font-semibold text-blue-900">Total Allocated:</span>
                            <span class="font-semibold text-blue-900">
                                <span id="allocated-total">0.00</span> {{ $group->currency }}
                            </span>
                        </div>
                        <p id="total-warning" class="text-xs text-red-600 mt-2 hidden">
                            ⚠️ Total allocated doesn't match expense amount
                        </p>
                    </div>
                </div>
            </div>

            @error('splits')
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-700">{{ $message }}</p>
                </div>
            @enderror

            <!-- Group Members Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-gray-700"><strong>Group:</strong> {{ $group->name }}</p>
                <p class="text-sm text-gray-700"><strong>Members:</strong> {{ $members->count() }}</p>
                <p class="text-sm text-gray-700"><strong>Currency:</strong> {{ $group->currency }}</p>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6">
                <button
                    type="submit"
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                >
                    Add Expense
                </button>
                <a
                    href="{{ route('groups.show', $group) }}"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold text-center"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCustomSplits() {
    const splitType = document.getElementById('split_type').value;
    const customSplitsDiv = document.getElementById('custom-splits');

    if (splitType === 'custom') {
        customSplitsDiv.classList.remove('hidden');
        updateTotal();
    } else {
        customSplitsDiv.classList.add('hidden');
    }
}

function updateTotal() {
    const splits = document.querySelectorAll('input[name^="splits["]');
    let total = 0;

    splits.forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const allocatedDisplay = document.getElementById('allocated-total');
    const warningDiv = document.getElementById('total-warning');

    allocatedDisplay.textContent = total.toFixed(2);

    if (Math.abs(total - amount) > 0.01) {
        warningDiv.classList.remove('hidden');
    } else {
        warningDiv.classList.add('hidden');
    }
}

function updateTotalDisplay() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    document.getElementById('total-display').textContent = amount.toFixed(2);
    updateTotal();
}

// Update total display on amount change
document.getElementById('amount').addEventListener('change', updateTotalDisplay);
document.getElementById('amount').addEventListener('input', updateTotalDisplay);

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleCustomSplits);
</script>
@endsection
