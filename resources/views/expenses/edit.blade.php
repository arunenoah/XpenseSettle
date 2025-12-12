@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Edit Expense</h1>
        <p class="text-gray-600 mb-6">Update expense details for <strong>{{ $group->name }}</strong></p>

        <form action="{{ route('groups.expenses.update', ['group' => $group, 'expense' => $expense]) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Expense Title -->
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Expense Title</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $expense->title) }}"
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
                >{{ old('description', $expense->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Amount</label>
                <div class="relative">
                    <span class="absolute left-4 top-3 sm:top-4 text-gray-600 font-semibold">
                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}
                    </span>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        value="{{ old('amount', $expense->amount) }}"
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
                    value="{{ old('date', $expense->date) }}"
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
                    <option value="equal" {{ old('split_type', $expense->split_type) === 'equal' ? 'selected' : '' }}>Equal Split (divide evenly)</option>
                    <option value="custom" {{ old('split_type', $expense->split_type) === 'custom' ? 'selected' : '' }}>Custom Split (specify amounts)</option>
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
                                    {{ $member->getMemberName() }}
                                    @if($member->isContact())
                                        <span class="ml-2 inline-block px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded text-xs font-semibold">✨ Contact</span>
                                    @endif
                                </label>
                                <div class="relative flex-1">
                                    <span class="absolute right-3 top-2 sm:top-3 text-gray-600 text-sm">
                                        {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : ($group->currency === 'AUD' ? '$' : '₹'))) }}
                                    </span>
                                    <input
                                        type="number"
                                        id="split-{{ $member->id }}"
                                        name="splits[{{ $member->id }}]"
                                        step="0.01"
                                        min="0"
                                        value="{{ old('splits.' . $member->id, $currentSplits[$member->id] ?? 0) }}"
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

            <!-- Current Attachments -->
            @if($expense->attachments->count() > 0)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Current Attachments</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($expense->attachments as $attachment)
                            <div class="border-2 border-blue-200 rounded-lg p-3 flex items-center justify-between">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span>📎</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $attachment->file_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $attachment->file_size_kb }} KB</p>
                                    </div>
                                </div>
                                <a href="{{ route('attachments.download', ['attachment' => $attachment->id]) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700 font-bold ml-2 flex-shrink-0">
                                    View
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Add More Attachments -->
            <div>
                <label for="attachments" class="block text-sm font-semibold text-gray-700 mb-2">Add More Attachments (Optional)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer" id="dropzone">
                    <input
                        type="file"
                        id="attachments"
                        name="attachments[]"
                        multiple
                        accept="image/png,image/jpeg,application/pdf"
                        class="hidden"
                        onchange="updateFileList()"
                    />
                    <div class="space-y-2">
                        <p class="text-2xl">📎</p>
                        <p class="text-sm font-semibold text-gray-700">Click to upload or drag and drop</p>
                        <p class="text-xs text-gray-500">PNG, JPEG, or PDF • Max 5MB per file</p>
                    </div>
                </div>

                <!-- File List -->
                <div id="file-list" class="mt-4 space-y-2 hidden">
                    <p class="text-sm font-semibold text-gray-700">New Files:</p>
                    <ul id="files" class="space-y-2"></ul>
                </div>

                @error('attachments')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Expense Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-gray-700"><strong>Created by:</strong> {{ $expense->payer->name }}</p>
                <p class="text-sm text-gray-700"><strong>Created on:</strong> {{ $expense->created_at->format('M d, Y H:i') }}</p>
                <p class="text-sm text-gray-700"><strong>Status:</strong>
                    <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $expense->status === 'fully_paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $expense->status)) }}
                    </span>
                </p>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6">
                <button
                    type="submit"
                    class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold"
                >
                    Save Changes
                </button>
                <a
                    href="{{ route('groups.expenses.show', ['group' => $group, 'expense' => $expense]) }}"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold text-center"
                >
                    Cancel
                </a>
            </div>
        </form>

        <!-- Delete Section -->
        <div class="mt-8 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="font-semibold text-red-900 mb-2">Delete Expense</h3>
            <p class="text-sm text-red-800 mb-4">Once you delete an expense, it cannot be recovered.</p>
            <form action="{{ route('groups.expenses.destroy', ['group' => $group, 'expense' => $expense]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold"
                >
                    Delete Expense
                </button>
            </form>
        </div>
    </div>
</div>

<script nonce="{{ request()->attributes->get(\'nonce\', \'\') }}">
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

// File upload handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('attachments');

dropzone.addEventListener('click', () => fileInput.click());

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-blue-500', 'bg-blue-50');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-blue-500', 'bg-blue-50');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-blue-500', 'bg-blue-50');
    fileInput.files = e.dataTransfer.files;
    updateFileList();
});

function updateFileList() {
    const files = fileInput.files;
    const fileList = document.getElementById('file-list');
    const fileListUl = document.getElementById('files');

    fileListUl.innerHTML = '';

    if (files.length > 0) {
        fileList.classList.remove('hidden');
        Array.from(files).forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200';
            li.innerHTML = `
                <span class="flex items-center gap-2 text-sm">
                    <span>${file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name}</span>
                    <span class="text-xs text-gray-500">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                </span>
                <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-700 font-bold">✕</button>
            `;
            fileListUl.appendChild(li);
        });
    } else {
        fileList.classList.add('hidden');
    }
}

function removeFile(index) {
    const dt = new DataTransfer();
    const files = fileInput.files;

    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }

    fileInput.files = dt.files;
    updateFileList();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleCustomSplits);
</script>
@endsection
