@extends('layouts.app')

@section('title', 'Add Expense to ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white min-h-screen">
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-2">
                    💳 Add Expense
                </h1>
                <p class="text-lg text-gray-600">
                    to <strong>{{ $group->name }}</strong> · {{ $group->members->count() }} members
                </p>
            </div>

            <!-- Step Indicator -->
            <div class="mb-8 bg-white rounded-lg shadow-sm p-6 border border-blue-200">
                <h3 class="text-sm font-bold text-gray-600 uppercase mb-4">Quick Add Expense Flow</h3>
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm mb-1">1</div>
                        <span class="text-xs font-semibold text-gray-700">Scan Receipt</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mb-6"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm mb-1">2</div>
                        <span class="text-xs font-semibold text-gray-700">Assign Items</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-300 mx-2 mb-6"></div>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm mb-1">3</div>
                        <span class="text-xs font-semibold text-gray-700">Review & Save</span>
                    </div>
                </div>
            </div>

    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
            <!-- Recommended Path Banner -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded">
                <p class="text-blue-900 text-sm">
                    <strong>💡 Pro Tip:</strong> Upload a receipt photo for the fastest way to add expenses. Our OCR will extract items automatically!
                </p>
            </div>

        <form action="{{ route('groups.expenses.store', $group) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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
                <div class="grid grid-cols-2 gap-3">
                    <!-- Equal Split Option -->
                    <label class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-blue-400 hover:bg-blue-50" id="option-equal">
                        <input
                            type="radio"
                            name="split_type"
                            value="equal"
                            {{ old('split_type', 'equal') === 'equal' ? 'checked' : '' }}
                            onchange="toggleCustomSplits()"
                            class="mt-1"
                            required
                        />
                        <div class="ml-3 flex-1">
                            <p class="font-semibold text-gray-900">Equal Split</p>
                            <p class="text-xs text-gray-600 mt-1">Divide evenly among members</p>
                        </div>
                    </label>

                    <!-- Custom Split Option -->
                    <label class="relative flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-blue-400 hover:bg-blue-50" id="option-custom">
                        <input
                            type="radio"
                            name="split_type"
                            value="custom"
                            {{ old('split_type') === 'custom' ? 'checked' : '' }}
                            onchange="toggleCustomSplits()"
                            class="mt-1"
                            required
                        />
                        <div class="ml-3 flex-1">
                            <p class="font-semibold text-gray-900">Custom Split</p>
                            <p class="text-xs text-gray-600 mt-1">Specify amounts per person</p>
                        </div>
                    </label>
                </div>

                <!-- Hidden input to maintain form compatibility -->
                <input type="hidden" id="split_type" name="split_type_hidden" value="{{ old('split_type', 'equal') }}" />
                @error('split_type')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <script>
            // Update styling on load and when options change
            document.querySelectorAll('input[name="split_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    updateSplitTypeUI();
                });
            });

            function updateSplitTypeUI() {
                const equalOption = document.getElementById('option-equal');
                const customOption = document.getElementById('option-custom');
                const equalRadio = equalOption.querySelector('input');
                const customRadio = customOption.querySelector('input');

                if (equalRadio.checked) {
                    equalOption.classList.add('border-blue-500', 'bg-blue-50', 'border-2');
                    equalOption.classList.remove('border-gray-200');
                    customOption.classList.remove('border-blue-500', 'bg-blue-50');
                    customOption.classList.add('border-gray-200');
                } else {
                    customOption.classList.add('border-blue-500', 'bg-blue-50', 'border-2');
                    customOption.classList.remove('border-gray-200');
                    equalOption.classList.remove('border-blue-500', 'bg-blue-50');
                    equalOption.classList.add('border-gray-200');
                }
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                updateSplitTypeUI();
            });
            </script>

            <!-- Custom Splits Section -->
            <div id="custom-splits" class="hidden">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h3 class="font-semibold text-blue-900 mb-3">Specify how much each person owes</h3>
                    <p class="text-sm text-blue-800 mb-4">Total must equal <strong id="total-display">0.00</strong> {{ $group->currency }}</p>

                    <div class="space-y-3">
                        @foreach($members as $member)
                            <div class="flex items-center gap-3">
                                <label for="split-{{ $member->id }}" class="flex-1 text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <span>{{ $member->getMemberName() }}</span>
                                    @if($member->isContact())
                                        <span class="text-xs px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded">Contact</span>
                                    @endif
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

            <!-- Attachments -->
            <div>
                <label for="attachments" class="block text-sm font-semibold text-gray-700 mb-2">Attachments (Optional)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 hover:bg-blue-50 transition-all cursor-pointer" id="dropzone">
                    <input
                        type="file"
                        id="attachments"
                        name="attachments[]"
                        multiple
                        accept="image/png,image/jpeg,application/pdf"
                        class="hidden"
                    />
                    <div class="space-y-2">
                        <p class="text-2xl">📎</p>
                        <p class="text-sm font-semibold text-gray-700">Click to upload or drag and drop</p>
                        <p class="text-xs text-gray-500">PNG, JPEG, or PDF • Max 5MB per file</p>
                    </div>
                </div>

                <!-- File List -->
                <div id="file-list" class="mt-4 space-y-2 hidden">
                    <p class="text-sm font-semibold text-gray-700">Selected Files:</p>
                    <ul id="files" class="space-y-2"></ul>
                </div>

                <!-- Plan Status Badge -->
                <div class="mt-4 flex items-center justify-between bg-gray-50 rounded-lg p-3">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700">Current Plan:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold 
                            {{ $planName === 'Lifetime' ? 'bg-purple-100 text-purple-700' : ($planName === 'Trip Pass' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ $planName }}
                        </span>
                    </div>
                    @if($planName === 'Free')
                        <span class="text-xs text-gray-600">{{ $remainingOCRScans }} OCR scans remaining</span>
                    @else
                        <span class="text-xs text-green-600">✓ Unlimited OCR scans</span>
                    @endif
                </div>

                <!-- OCR Processing Button -->
                @if($canUseOCR)
                    <div id="ocr-section" class="mt-6 hidden bg-gradient-to-r from-green-50 to-blue-50 border-2 border-green-300 rounded-lg p-6">
                        <div class="mb-4">
                            <h4 class="font-bold text-gray-900 mb-2">✨ Smart Receipt Scanning (Our Superpower!)</h4>
                            <p class="text-sm text-gray-600">Let our OCR extract all line items automatically and assign them to group members. Much faster than manual entry!</p>
                            @if($planName === 'Free')
                                <p class="text-xs text-orange-600 mt-2">⚠️ You have {{ $remainingOCRScans }} free scans remaining for this trip</p>
                            @endif
                        </div>
                        <button
                            type="button"
                            id="process-ocr-btn"
                            class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:from-green-700 hover:to-blue-700 transition-colors font-bold flex items-center justify-center gap-2 text-lg"
                        >
                            <span id="ocr-btn-text">🔍 Extract Line Items from Receipt</span>
                            <span id="ocr-spinner" class="hidden">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                @else
                    <!-- Upgrade Prompt -->
                    <div id="ocr-section" class="mt-6 hidden bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-300 rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-4xl mb-3">🔒</div>
                            <h4 class="font-bold text-gray-900 mb-2">OCR Limit Reached</h4>
                            <p class="text-sm text-gray-600 mb-4">You've used all 5 free OCR scans for this trip. Upgrade to continue scanning receipts!</p>
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Testing: Manual activation buttons (replace with payment in production) -->
                                <form action="{{ route('groups.activate-trip-pass', $group) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-colors font-bold text-center">
                                        🎫 Activate Trip Pass (Test)
                                    </button>
                                </form>
                                <form action="{{ route('user.activate-lifetime') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-bold text-center">
                                        ⭐ Activate Lifetime (Test)
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-500 mt-3">Testing mode - Click to activate instantly</p>
                            <p class="text-xs text-gray-400 mt-1">In production, this will redirect to payment</p>
                        </div>
                    </div>
                @endif

                <!-- Extracted Items Section -->
                <div id="items-section" class="mt-6 hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 rounded-lg p-4 sm:p-6 mb-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-2xl">✅</span>
                            <div>
                                <h3 class="font-bold text-green-900 text-lg">Line Items Extracted</h3>
                                <p class="text-xs text-green-700">Assign to members • Items without assignee split equally</p>
                            </div>
                        </div>

                        <!-- Items Display as Pills -->
                        <div id="items-list" class="flex flex-wrap gap-2 mb-4 bg-white p-3 rounded-lg border border-green-200">
                            <!-- Populated by JavaScript -->
                        </div>

                        <!-- Summary Row -->
                        <div class="flex items-center justify-between gap-3 mb-4 p-3 bg-white rounded-lg border border-green-200">
                            <div>
                                <p class="text-xs text-gray-600">Total from receipt</p>
                                <p class="font-bold text-lg text-green-600">
                                    $<span id="ocr-total">0.00</span>
                                </p>
                            </div>
                            <button
                                type="button"
                                id="add-item-btn"
                                class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors font-semibold text-xs flex items-center gap-1 flex-shrink-0"
                            >
                                + Add
                            </button>
                        </div>

                        <!-- OCR Confidence Warning -->
                        <div id="ocr-warning" class="text-xs text-orange-600 mb-4 p-2 bg-orange-50 rounded border border-orange-200 hidden">
                            ⚠️ <span id="warning-text"></span>
                        </div>

                        <!-- Use Extracted Button -->
                        <button
                            type="button"
                            id="use-extracted-btn"
                            class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 active:bg-green-800 transition-colors font-semibold text-sm flex items-center justify-center gap-2"
                        >
                            <span>✓ Use Items & Auto-Split</span>
                        </button>
                    </div>
                </div>

                @error('attachments')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Hidden inputs for item assignments -->
            <input type="hidden" id="items-data" name="items_json" value="[]" />

            <!-- Add Item Modal -->
            <div id="add-item-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Missing Item</h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Item Name</label>
                            <input type="text" id="new-item-name" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Milk" />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Qty</label>
                                <input type="number" id="new-item-qty" class="w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="1" min="1" step="1" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Unit Price</label>
                                <input type="number" id="new-item-unit-price" class="w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" min="0" step="0.01" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Total</label>
                                <input type="number" id="new-item-total" class="w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00" min="0" step="0.01" readonly />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button type="button" id="add-item-confirm-btn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                            ✓ Add Item
                        </button>
                        <button type="button" id="add-item-cancel-btn" class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-semibold">
                            ✕ Cancel
                        </button>
                    </div>
                </div>
            </div>

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

// Listen for file input changes via the input element
fileInput.addEventListener('change', updateFileList);

function updateFileList() {
    const files = fileInput.files;
    const fileListContainer = document.getElementById('file-list');
    const fileListUl = document.getElementById('files');
    const ocrSection = document.getElementById('ocr-section');

    // Safety check - ensure all DOM elements exist before processing
    if (!fileListContainer || !fileListUl || !ocrSection) {
        console.error('Required DOM elements not found for file list update');
        return;
    }

    fileListUl.innerHTML = '';

    if (files && files.length > 0) {
        fileListContainer.classList.remove('hidden');
        ocrSection.classList.remove('hidden');
        Array.from(files).forEach((file, index) => {
            const li = document.createElement('li');
            const isImage = file.type.startsWith('image/');

            if (isImage) {
                // Image file - show thumbnail preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    li.className = 'flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors';
                    li.innerHTML = `
                        <span class="flex items-center gap-3 text-sm flex-1 min-w-0">
                            <img src="${e.target.result}" alt="${file.name}" class="w-16 h-16 rounded object-cover flex-shrink-0 border border-blue-300 shadow-sm" />
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-gray-900 truncate">${file.name}</div>
                                <div class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</div>
                            </div>
                        </span>
                        <button type="button" class="ml-2 text-red-600 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded font-bold flex-shrink-0" onclick="removeFile(${index})">✕</button>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                // Non-image file - show file icon
                li.className = 'flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors';
                li.innerHTML = `
                    <span class="flex items-center gap-2 text-sm flex-1">
                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0015.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-900 truncate">${file.name}</div>
                            <div class="text-xs text-gray-500">${(file.size / 1024).toFixed(1)} KB</div>
                        </div>
                    </span>
                    <button type="button" class="ml-2 text-red-600 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded font-bold flex-shrink-0" onclick="removeFile(${index})">✕</button>
                `;
            }
            fileListUl.appendChild(li);
        });
    } else {
        fileListContainer.classList.add('hidden');
        ocrSection.classList.add('hidden');
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

<!-- Tesseract.js for OCR - Load without async to ensure proper initialization -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.0/dist/tesseract.min.js"></script>

<script>
const members = {!! json_encode($members->map(fn($m) => ['id' => $m->id, 'name' => $m->getMemberName(), 'isContact' => $m->isContact()])->values()) !!};
let extractedItems = [];
let currentFile = null;

// Check if Tesseract is loaded
function waitForTesseract(maxAttempts = 50) {
    return new Promise((resolve, reject) => {
        let attempts = 0;
        const checkInterval = setInterval(() => {
            if (typeof Tesseract !== 'undefined') {
                clearInterval(checkInterval);
                resolve();
            } else if (attempts++ > maxAttempts) {
                clearInterval(checkInterval);
                reject(new Error('Tesseract.js failed to load after ' + maxAttempts + ' attempts'));
            }
        }, 100);
    });
}

// Enhanced file handling
function handleFileSelect() {
    updateFileList();
    const files = document.getElementById('attachments').files;
    if (files.length > 0) {
        document.getElementById('ocr-section').classList.remove('hidden');
        currentFile = files[0];
    } else {
        document.getElementById('ocr-section').classList.add('hidden');
    }
}

// Image preprocessing for better OCR
async function preprocessImage(imageUrl) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size to image size
            canvas.width = img.width;
            canvas.height = img.height;
            
            // Draw original image
            ctx.drawImage(img, 0, 0);
            
            // Get image data
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            
            // Convert to grayscale and increase contrast
            for (let i = 0; i < data.length; i += 4) {
                // Grayscale conversion
                const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                
                // Increase contrast (simple threshold)
                const threshold = 128;
                const contrast = 1.5;
                let adjusted = (gray - threshold) * contrast + threshold;
                adjusted = Math.max(0, Math.min(255, adjusted));
                
                // Apply to all channels
                data[i] = adjusted;     // R
                data[i + 1] = adjusted; // G
                data[i + 2] = adjusted; // B
            }
            
            // Put processed image back
            ctx.putImageData(imageData, 0, 0);
            
            // Return as data URL
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = reject;
        img.src = imageUrl;
    });
}

// Process OCR on uploaded receipt
document.getElementById('process-ocr-btn').addEventListener('click', async function() {
    const files = document.getElementById('attachments').files;
    if (files.length === 0) {
        alert('Please select a receipt image first');
        return;
    }

    const btn = document.getElementById('process-ocr-btn');
    const btnText = document.getElementById('ocr-btn-text');
    const spinner = document.getElementById('ocr-spinner');

    btn.disabled = true;
    btnText.textContent = 'Processing...';
    spinner.classList.remove('hidden');

    try {
        // Wait for Tesseract to load once
        console.log('Waiting for Tesseract.js library to load...');
        await waitForTesseract();
        console.log('Tesseract library loaded successfully');

        // Initialize Tesseract worker once
        const { createWorker } = Tesseract;
        console.log('Creating Tesseract worker...');
        const worker = await createWorker('eng', 1, {
            logger: m => console.log(m)
        });
        
        // Configure for receipt scanning
        await worker.setParameters({
            tessedit_char_whitelist: '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz$.,:-/()* ',
            tessedit_pageseg_mode: '6', // Assume uniform block of text
        });

        let allItems = [];
        let itemId = 1;

        // Process each file
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Skip non-image files
            if (!file.type.startsWith('image/')) {
                console.log('Skipping non-image file:', file.name);
                continue;
            }

            const imageUrl = URL.createObjectURL(file);
            console.log(`\n[${i + 1}/${files.length}] Processing file: ${file.name}`);
            btnText.textContent = `Processing ${i + 1}/${files.length}: ${file.name}`;

            // Preprocess image for better OCR
            console.log('Preprocessing image...');
            const preprocessedImage = await preprocessImage(imageUrl);

            console.log('Starting recognition...');
            const result = await worker.recognize(preprocessedImage);
            const text = result.data.text;

            console.log('OCR text extracted:', text);

            // Parse receipt and extract items
            console.log('Parsing receipt...');
            const items = parseTableFormat(text) || parseReceipt(text);

            if (items && items.length > 0) {
                console.log(`Found ${items.length} items in ${file.name}`);
                
                // Add file name to each item and renumber IDs
                items.forEach(item => {
                    item.id = itemId++;
                    item.source_file = file.name;
                });
                
                allItems = allItems.concat(items);
            } else {
                console.log(`No items found in ${file.name}`);
            }

            // Clean up
            URL.revokeObjectURL(imageUrl);
        }

        await worker.terminate();

        console.log(`\nTotal items extracted from ${files.length} file(s): ${allItems.length}`);

        if (allItems.length === 0) {
            alert('No items found in any of the uploaded receipts. Please check the image quality and try again.');
            return;
        }

        // Display all items
        extractedItems = allItems;
        displayExtractedItems(extractedItems);
        document.getElementById('items-section').classList.remove('hidden');

        console.log('All items displayed successfully');

        // Increment OCR scan counter for free users
        @if($planName === 'Free')
            fetch('{{ route("groups.increment-ocr", $group) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            }).catch(err => console.error('Failed to increment OCR counter:', err));
        @endif

    } catch (error) {
        console.error('OCR Error:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);

        let errorMsg = error.message || 'Unknown error occurred';

        // Provide helpful messages for common errors
        if (error.message && error.message.includes('Tesseract')) {
            errorMsg = 'OCR Library loading issue. Please:\n1. Refresh the page\n2. Check your internet connection\n3. Try again';
        } else if (error.message && error.message.includes('blob:')) {
            errorMsg = 'Could not read image file. Please ensure the image file is valid (PNG, JPG, or PDF).';
        }

        alert('Error processing receipt:\n\n' + errorMsg);
    } finally {
        btn.disabled = false;
        btnText.textContent = '🔍 Extract Line Items from Receipt';
        spinner.classList.add('hidden');
    }
});

// Parse table-based invoice format (e.g., Julitha Van, service invoices)
function parseTableFormat(text) {
    const lines = text.split('\n').map(l => l.trim()).filter(l => l);
    const items = [];
    let itemId = 1;
    
    console.log('Attempting table format parsing...');
    
    // Patterns for table-based data
    const tableRowPattern = /^([A-Za-z\s]+)\s+(\d+\.?\d*)\s*([A-Za-z]+)?\s+\$?(\d+\.?\d{2})\s+\$?(\d+\.?\d{2})$/;
    
    // Keywords to skip (totals, headers, etc.)
    const skipKeywords = ['sub-total', 'subtotal', 'sub total', 'booking total', 'trip total', 
                          'total paid', 'total due', 'payments made', 'payment:'];
    
    // Header-only keywords (skip if line is ONLY this keyword)
    const headerKeywords = ['amount', 'rate', 'usage', 'description', 'other charges', 
                           'time and distance charges', 'charges'];
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const lineUpper = line.toUpperCase();
        
        // Skip total lines
        if (skipKeywords.some(kw => lineUpper.includes(kw.toUpperCase()))) {
            console.log('Skipping table total:', line);
            continue;
        }
        
        // Skip header-only lines (but allow items that contain these words)
        if (headerKeywords.some(kw => lineUpper === kw.toUpperCase())) {
            console.log('Skipping table header:', line);
            continue;
        }
        
        // Try to match table row pattern: "Hours  2.5 Hours  $15.10  $37.75"
        const match = line.match(tableRowPattern);
        
        if (match) {
            const name = match[1].trim();
            const quantity = parseFloat(match[2]);
            const unit = match[3] || '';
            const rate = parseFloat(match[4]);
            const amount = parseFloat(match[5]);
            
            console.log('Found table row:', { name, quantity, unit, rate, amount });
            
            // Validate
            if (name.length > 0 && quantity > 0 && amount > 0) {
                items.push({
                    id: itemId++,
                    name: `${name}${unit ? ' (' + unit + ')' : ''}`,
                    quantity: quantity,
                    unit_price: rate,
                    total_price: amount
                });
            }
        } else {
            // Try simpler pattern: "Hours    2.5 Hours    $15.10    $37.75"
            // Or: "Damage Cover    $6.25" (single amount at end)
            
            // First try: Split by multiple spaces or tabs
            let parts = line.split(/\s{2,}|\t+/).filter(p => p.trim());
            
            // If that didn't work well, try finding price at end and splitting from there
            if (parts.length < 2) {
                const priceAtEndMatch = line.match(/^(.+?)\s+\$?(\d+\.?\d{2})$/);
                if (priceAtEndMatch) {
                    parts = [priceAtEndMatch[1].trim(), priceAtEndMatch[2]];
                    console.log('Found price at end pattern:', parts);
                }
            }
            
            if (parts.length >= 2) {
                const name = parts[0];
                let quantity = 1;
                let rate = 0;
                let amount = 0;
                
                // Look for numbers in all parts (including the name part for embedded numbers)
                const numbers = [];
                for (let j = 0; j < parts.length; j++) {
                    // Find all numbers in this part
                    const numMatches = parts[j].matchAll(/\$?(\d+\.?\d*)/g);
                    for (const match of numMatches) {
                        const num = parseFloat(match[1]);
                        if (!isNaN(num)) {
                            numbers.push(num);
                        }
                    }
                }
                
                console.log('Line:', line, '-> Parts:', parts, '-> Numbers:', numbers);
                
                // If we have 3+ numbers: quantity, rate, amount
                if (numbers.length >= 3) {
                    quantity = numbers[0];
                    rate = numbers[1];
                    amount = numbers[2];
                } else if (numbers.length === 2) {
                    // If we have 2 numbers: rate, amount (quantity = 1)
                    rate = numbers[0];
                    amount = numbers[1];
                } else if (numbers.length === 1) {
                    // If we have 1 number: amount (quantity = 1, rate = amount)
                    amount = numbers[0];
                    rate = amount;
                }
                
                // Validate
                const hasLetters = /[a-zA-Z]/.test(name);
                const isValidAmount = amount > 0 && amount < 10000; // Reasonable max for table items
                const isReasonableQuantity = quantity > 0 && quantity <= 100; // Reasonable quantity
                const notSkipKeyword = !skipKeywords.some(kw => name.toUpperCase().includes(kw.toUpperCase()));
                const notHeaderOnly = !headerKeywords.some(kw => name.toUpperCase() === kw.toUpperCase());
                
                // Additional validation: name should not be mostly numbers or symbols
                const letterCount = (name.match(/[a-zA-Z]/g) || []).length;
                const digitCount = (name.match(/\d/g) || []).length;
                const hasMoreLettersThanDigits = letterCount >= digitCount; // Allow equal for items like "7-ELEVEN"
                
                // Name should not contain common OCR garbage patterns
                const hasOCRGarbage = /\d{4,}/.test(name) || // 4+ consecutive digits (phone numbers, etc.)
                                      name.split(/\s+/).length > 8 || // Too many words (likely OCR error)
                                      /^[a-z]{1,5}$/.test(name.toLowerCase()) || // Short lowercase gibberish
                                      /^[A-Z]{1,2}$/.test(name) || // 1-2 uppercase letters only
                                      name.length < 3; // Too short to be a real item
                
                // Check for common garbage patterns
                const hasGarbageWords = /\b(wesc|awl|deblt|lleven|atc|el)\b/i.test(name);
                
                if (hasLetters && isValidAmount && isReasonableQuantity && notSkipKeyword && notHeaderOnly && hasMoreLettersThanDigits && !hasOCRGarbage && !hasGarbageWords && name.length > 1) {
                    console.log('✓ Found table item:', { name, quantity, rate, amount });
                    items.push({
                        id: itemId++,
                        name: name,
                        quantity: quantity,
                        unit_price: rate,
                        total_price: amount
                    });
                } else {
                    console.log('✗ Rejected:', { name, hasLetters, isValidAmount, notSkipKeyword, notHeaderOnly });
                }
            }
        }
    }
    
    console.log('Table format parsing complete. Found', items.length, 'items');
    
    // Return items if we found any, otherwise return null to try other parsers
    return items.length > 0 ? items : null;
}

// Parse receipt text and extract items
function parseReceipt(text) {
    const lines = text.split('\n').map(l => l.trim()).filter(l => l);
    const items = [];
    let total = 0;

    console.log('Parsing receipt text:');
    console.log('Lines:', lines);

    // Enhanced price patterns for different formats (including negative for discounts)
    const pricePatterns = [
        /\$\s*-?(\d+[.,]\d{2})/,           // $12.34 or $-12.34
        /-\$\s*(\d+[.,]\d{2})/,            // -$12.34
        /(-?\d+[.,]\d{2})\s*\$/,           // 12.34$ or -12.34$
        /(-?\d+[.,]\d{2})\s*(?:AUD|USD|EUR|GBP)?$/i,  // 12.34 AUD or -12.34 AUD
        /(?:^|\s)(-?\d+[.,]\d{2})(?:\s|$)/ // 12.34 (standalone) or -12.34
    ];
    
    const quantityPatterns = [
        /\b[x×*]\s*(\d+)\b/i,            // x2, ×2, *2
        /\b(\d+)\s*[x×*]\b/i,            // 2x, 2×, 2*
        /qty:?\s*(\d+)/i,                 // qty:2, qty 2
        /^(\d+)\s+/                       // Leading number
    ];

    // Enhanced ignore keywords - be more aggressive
    const ignoreKeywords = ['welcome', 'thank', 'thanks', 'total', 'subtotal', 'net', 'cash', 'card',
                            'payment', 'paid', 'change', 'gst', 'vat', 'tax', 'discount', 'member',
                            'loyalty', 'phone', 'address', 'date', 'time', 'store', 'location',
                            'receipt', 'invoice', 'account', 'balance', 'till', 'cashier', 'items', 'qty',
                            'eftpos', 'approved', 'auth', 'terminal', 'reference', 'customer', 'copy',
                            'debit', 'credit', 'visa', 'mastercard', 'amex', 'rounded', 'rounding',
                            'tendered', 'surcharge', 'service', 'fee', 'purchase', 'abn', 'acn', 'ph:',
                            'included', 'incl', 'excl', 'excluding', 'including', 'staff', 'sale'];

    let itemId = 1;
    let pendingName = null;

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const lineUpper = line.toUpperCase();

        // Skip very short lines
        if (line.length < 2) {
            pendingName = null;
            continue;
        }

        // Skip lines containing any ignore keywords (not just exact match)
        if (ignoreKeywords.some(kw => lineUpper.includes(kw.toUpperCase()))) {
            console.log('Skipping keyword line:', line);
            pendingName = null;
            continue;
        }

        // Find price in this line using multiple patterns
        let priceMatch = null;
        let priceStr = null;
        
        for (const pattern of pricePatterns) {
            priceMatch = line.match(pattern);
            if (priceMatch) {
                priceStr = priceMatch[1];
                break;
            }
        }

        if (priceMatch && priceStr) {
            // This line has a price (can be negative for discounts)
            let price = parseFloat(priceStr.replace(',', '.'));
            
            // Check if the original line had a minus sign for the price
            if (line.includes('-$') || line.match(/-\s*\d+[.,]\d{2}/)) {
                price = Math.abs(price) * -1; // Ensure it's negative
            }

            let name = '';

            // If we have a pending name from previous line, use it
            if (pendingName) {
                name = pendingName;
            } else {
                // Extract name from current line (everything before price)
                name = line.substring(0, priceMatch.index).trim();
            }

            console.log('Found price line:', line, '-> name:', name, 'price:', price);

            // Clean up name
            // Remove leading product codes: "123456 ITEM" → "ITEM"
            name = name.replace(/^\d{4,}\s+/, '').trim();
            // Remove trailing product codes: "ITEM 123456" → "ITEM"
            name = name.replace(/\s+\d{4,}$/, '').trim();
            // Remove quantity patterns
            name = name.replace(/\s+[x×]\s*\d+.*$/i, '').trim();
            // Remove trailing single/double digit numbers (likely quantity)
            name = name.replace(/\s+\d{1,2}$/, '').trim();
            // Remove single letters/codes at start
            name = name.replace(/^[a-z]\s+/i, '').trim();

            // Extract quantity using multiple patterns
            let quantity = 1;
            
            // First, try to find quantity in the cleaned name part (before price)
            const nameBeforePrice = line.substring(0, priceMatch.index).trim();
            const numbersInName = nameBeforePrice.match(/\b(\d+)\b/g);
            
            if (numbersInName && numbersInName.length > 0) {
                // Take the last number before the price as quantity (most likely to be qty)
                const lastNum = parseInt(numbersInName[numbersInName.length - 1]);
                
                // Only use as quantity if it's a reasonable quantity (not part of product name like "30G")
                // Check if the number is followed by a unit indicator (G, ML, KG, etc.)
                const hasUnitAfter = /\d+(G|ML|KG|L|OZ|GM|MG)\b/i.test(nameBeforePrice);
                
                if (lastNum > 0 && lastNum < 20 && !hasUnitAfter) {
                    // Only accept quantities up to 20 to avoid mistaking product codes/weights
                    quantity = lastNum;
                }
            }
            
            // If that didn't work, try the quantity patterns
            if (quantity === 1) {
                for (const pattern of quantityPatterns) {
                    const qtyMatch = line.match(pattern);
                    if (qtyMatch) {
                        quantity = parseInt(qtyMatch[1]);
                        if (quantity > 0 && quantity < 100) { // Reasonable quantity range
                            break;
                        }
                    }
                }
            }

            // Validate: name should have at least one letter
            const hasLetters = /[a-zA-Z]/.test(name);
            const isValidPrice = (price > 0 && price < 1000) || (price < 0 && price > -500); // Allow negative for discounts
            const isReasonableQuantity = quantity > 0 && quantity <= 50; // Reasonable quantity range
            
            // Additional validation: name should not be mostly numbers or symbols
            const letterCount = (name.match(/[a-zA-Z]/g) || []).length;
            const digitCount = (name.match(/\d/g) || []).length;
            const hasMoreLettersThanDigits = letterCount > digitCount;
            
            // Name should not contain common OCR garbage patterns
            const hasOCRGarbage = /\d{3,}/.test(name) || // 3+ consecutive digits
                                  /[^\w\s-/$.]/.test(name.replace(/[()&*]/g, '')) || // Special chars except common ones (allow $ for discounts)
                                  name.split(/\s+/).length > 8 || // Too many words (likely OCR error) - increased for discount descriptions
                                  /^[a-z]{1,4}$/.test(name.toLowerCase()) || // Short lowercase gibberish
                                  /^[A-Z]{1,2}$/.test(name) || // 1-2 uppercase letters only (like "El", "Fu")
                                  name.length < 3 || // Too short
                                  /^[.\s:]+$/.test(name); // Only punctuation/spaces
            
            // Check for common garbage patterns in name
            const hasGarbageWords = /\b(wesc|awl|deblt|lleven|atc|el)\b/i.test(name);

            if (hasLetters && isValidPrice && isReasonableQuantity && hasMoreLettersThanDigits && !hasOCRGarbage && !hasGarbageWords && name.length > 0) {
                console.log('✓ Adding item:', name, 'qty:', quantity, 'price:', price);
                items.push({
                    id: itemId++,
                    name: name,
                    quantity: quantity,
                    unit_price: parseFloat((price / quantity).toFixed(2)),
                    total_price: price,
                    assigned_to: null
                });
                total += price;
            } else {
                console.log('✗ Rejected item:', name, 'price:', price, {
                    hasLetters,
                    isValidPrice,
                    isReasonableQuantity,
                    hasMoreLettersThanDigits,
                    hasOCRGarbage,
                    hasGarbageWords
                });
            }

            pendingName = null;
        } else {
            // No price on this line - might be a product name for next line
            const hasLetters = /[a-zA-Z]/.test(line);
            const startsWithDigits = /^\d{4,}/.test(line); // Looks like a code line

            if (hasLetters && line.length > 2 && !startsWithDigits) {
                console.log('Storing as pending name:', line);
                pendingName = line;
            } else {
                pendingName = null;
            }
        }
    }

    console.log('Parsing complete. Found', items.length, 'items');
    if (items.length === 0) {
        console.warn('No items extracted from receipt. Full text:', text);
    }

    // Store total for validation
    extractedItems = items;
    extractedItems._total = total;

    return items;
}

// Display extracted items (editable)
function displayExtractedItems(items) {
    const itemsList = document.getElementById('items-list');
    itemsList.innerHTML = '';
    let total = 0;

    items.forEach(item => {
        total += item.total_price;

        const assignedMember = members.find(m => m.id == item.assigned_to);
        const assignedName = assignedMember ? assignedMember.name : 'Unassigned';
        const pillColor = assignedMember ? 'bg-blue-100 border-blue-300' : 'bg-gray-100 border-gray-300';
        const textColor = assignedMember ? 'text-blue-900' : 'text-gray-900';

        const itemPill = document.createElement('div');
        itemPill.className = `group relative inline-flex flex-wrap gap-1 items-center px-3 py-2 rounded-full border-2 ${pillColor} cursor-pointer hover:shadow-md transition-all`;
        itemPill.id = `item-${item.id}`;
        itemPill.title = `${item.name} - Qty: ${item.quantity} @ ${getCurrencySymbol()}${item.unit_price.toFixed(2)}`;

        // Pill HTML with compact display
        const pillHtml = `
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-sm ${textColor}">
                    ${escapeHtml(item.name.length > 15 ? item.name.substring(0, 12) + '...' : item.name)}
                </span>
                <span class="text-xs font-bold text-gray-600">
                    ${getCurrencySymbol()}${item.total_price.toFixed(2)}
                </span>
                <span class="text-xs px-1.5 py-0.5 bg-white rounded-full text-gray-700 font-semibold">
                    ${assignedName.split(' ')[0]}
                </span>
            </div>

            <!-- Hidden tooltip with full details -->
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block bg-gray-900 text-white text-xs rounded py-2 px-3 whitespace-nowrap z-10">
                <div class="font-semibold">${escapeHtml(item.name)}</div>
                <div>Qty: ${item.quantity} × ${getCurrencySymbol()}${item.unit_price.toFixed(2)}</div>
                <div class="mt-1">Assign to: ${assignedName}</div>
            </div>

            <!-- Click to edit overlay -->
            <input type="hidden" class="item-member-hidden" data-item-id="${item.id}" value="${item.assigned_to || ''}" />
        `;

        // Edit mode
        const editHtml = `
            <div class="item-edit hidden">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Item Name</label>
                        <input type="text" name="edit_name_${item.id}" class="edit-name w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${escapeHtml(item.name)}" disabled />
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Qty</label>
                            <input type="number" name="edit_qty_${item.id}" class="edit-qty w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.quantity}" min="1" step="1" disabled />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Unit Price</label>
                            <input type="number" name="edit_unit_price_${item.id}" class="edit-unit-price w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.unit_price.toFixed(2)}" min="0" step="0.01" disabled />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Total</label>
                            <input type="number" name="edit_total_price_${item.id}" class="edit-total-price w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.total_price.toFixed(2)}" min="0" step="0.01" disabled />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="save-item-btn flex-1 px-3 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 font-semibold" data-item-id="${item.id}">
                            ✓ Save
                        </button>
                        <button type="button" class="cancel-item-btn flex-1 px-3 py-2 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400 font-semibold" data-item-id="${item.id}">
                            ✕ Cancel
                        </button>
                    </div>
                </div>
            </div>
        `;

        itemPill.innerHTML = pillHtml;

        // Add click handler to open edit modal
        itemPill.addEventListener('click', function() {
            openItemEditModal(item);
        });

        itemsList.appendChild(itemPill);
    });

    updateOCRTotal();
    attachItemEventListeners();
}

// Open item edit modal for pill-based UI
function openItemEditModal(item) {
    const modal = document.getElementById('add-item-modal');
    document.getElementById('new-item-name').value = item.name;
    document.getElementById('new-item-qty').value = item.quantity;
    document.getElementById('new-item-unit-price').value = item.unit_price.toFixed(2);
    document.getElementById('new-item-total').value = item.total_price.toFixed(2);

    const confirmBtn = document.getElementById('add-item-confirm-btn');
    confirmBtn.textContent = '✓ Update Item';
    confirmBtn.onclick = function() {
        updateItemFromModal(item.id);
    };

    modal.classList.remove('hidden');
    document.getElementById('new-item-name').focus();
}

function updateItemFromModal(itemId) {
    const name = document.getElementById('new-item-name').value.trim();
    const qty = parseInt(document.getElementById('new-item-qty').value) || 1;
    const unitPrice = parseFloat(document.getElementById('new-item-unit-price').value) || 0;

    if (!name) {
        alert('Item name cannot be empty');
        return;
    }

    if (qty <= 0 || unitPrice < 0) {
        alert('Please enter valid quantities and prices');
        return;
    }

    // Update item
    const item = extractedItems.find(i => i.id == itemId);
    if (item) {
        item.name = name;
        item.quantity = qty;
        item.unit_price = unitPrice;
        item.total_price = (qty * unitPrice).toFixed(2);
    }

    displayExtractedItems(extractedItems);
    document.getElementById('add-item-modal').classList.add('hidden');
}

// Attach event listeners for item functionality
function attachItemEventListeners() {
    // Right-click context menu or delete button on pills
    // For now, items can be edited by clicking on them or through "Add Missing Item" button

    // Add Item Button - Reset modal for new items
    document.getElementById('add-item-btn').addEventListener('click', function() {
        document.getElementById('new-item-name').value = '';
        document.getElementById('new-item-qty').value = '1';
        document.getElementById('new-item-unit-price').value = '';
        document.getElementById('new-item-total').value = '';

        const confirmBtn = document.getElementById('add-item-confirm-btn');
        confirmBtn.textContent = '✓ Add Item';
        confirmBtn.onclick = addNewItemFromModal;

        document.getElementById('add-item-modal').classList.remove('hidden');
        document.getElementById('new-item-name').focus();
    });

    // Quantity/Price calculation in modal
    document.getElementById('new-item-qty').addEventListener('change', calculateItemTotal);
    document.getElementById('new-item-unit-price').addEventListener('change', calculateItemTotal);
}

// Update OCR total display
function updateOCRTotal() {
    const total = extractedItems.reduce((sum, item) => sum + item.total_price, 0);
    document.getElementById('ocr-total').textContent = total.toFixed(2);
}

// Update custom splits based on item assignments
function updateSplitsFromItems() {
    const memberSplits = {};

    // Calculate total per member based on assigned items
    extractedItems.forEach(item => {
        if (item.assigned_to) {
            if (!memberSplits[item.assigned_to]) {
                memberSplits[item.assigned_to] = 0;
            }
            memberSplits[item.assigned_to] += item.total_price;
        }
    });

    // Update split inputs for all members
    members.forEach(member => {
        const splitInput = document.querySelector(`input[name="splits[${member.id}]"]`);
        if (splitInput) {
            splitInput.value = (memberSplits[member.id] || 0).toFixed(2);
        }
    });

    // Update total display
    updateTotal();
}

// Get currency symbol
function getCurrencySymbol() {
    const currency = '{{ $group->currency }}';
    const symbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'INR': '₹'
    };
    return symbols[currency] || currency;
}

// Escape HTML for security
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Use extracted items and auto-split
document.getElementById('use-extracted-btn').addEventListener('click', function() {
    console.log('Use extracted items clicked');

    const itemsData = [];
    const memberSelects = document.querySelectorAll('.item-member');

    // First, sync all member assignments from dropdowns to extractedItems
    memberSelects.forEach(select => {
        const itemId = parseInt(select.dataset.itemId);
        const memberId = select.value ? parseInt(select.value) : null;

        const item = extractedItems.find(i => i.id == itemId);
        if (item) {
            item.assigned_to = memberId;
            console.log('Updated item assignment:', item.name, 'to member:', memberId);
        }
    });

    // Collect item assignments for storage
    extractedItems.forEach(item => {
        itemsData.push({
            id: item.id,
            name: item.name,
            quantity: item.quantity,
            unit_price: item.unit_price,
            total_price: item.total_price,
            assigned_to: item.assigned_to
        });
    });

    // Store items data
    document.getElementById('items-data').value = JSON.stringify(itemsData);
    console.log('Stored items data:', itemsData);

    // Calculate total from all items
    const extractedTotal = extractedItems.reduce((sum, item) => sum + item.total_price, 0);

    // Update amount field
    const amountInput = document.getElementById('amount');
    amountInput.value = extractedTotal.toFixed(2);
    updateTotalDisplay();
    console.log('Amount set to:', extractedTotal.toFixed(2));

    // Auto-populate title if empty
    const titleInput = document.getElementById('title');
    if (!titleInput.value) {
        titleInput.value = 'Receipt - ' + new Date().toLocaleDateString();
    }

    // Switch to custom split mode
    const splitTypeSelect = document.getElementById('split_type');
    splitTypeSelect.value = 'custom';
    console.log('Split type changed to:', splitTypeSelect.value);

    // Show custom splits section
    toggleCustomSplits();

    // Wait a moment then update splits
    setTimeout(() => {
        updateSplitsFromItems();
        console.log('Splits updated from items');
    }, 100);

    alert('✅ Items extracted! Custom split mode activated.\n\nReview the splits below based on item assignments.');
});

// Add Item Modal Handlers
document.getElementById('add-item-btn').addEventListener('click', function() {
    document.getElementById('add-item-modal').classList.remove('hidden');
    document.getElementById('new-item-name').focus();
});

document.getElementById('add-item-cancel-btn').addEventListener('click', function() {
    document.getElementById('add-item-modal').classList.add('hidden');
    clearAddItemForm();
});

// Auto-calculate total when qty or unit price changes
document.getElementById('new-item-qty').addEventListener('input', calculateNewItemTotal);
document.getElementById('new-item-unit-price').addEventListener('input', calculateNewItemTotal);

function calculateNewItemTotal() {
    const qty = parseInt(document.getElementById('new-item-qty').value) || 0;
    const unitPrice = parseFloat(document.getElementById('new-item-unit-price').value) || 0;
    const total = (qty * unitPrice).toFixed(2);
    document.getElementById('new-item-total').value = total;
}

// Add Item Confirm
document.getElementById('add-item-confirm-btn').addEventListener('click', function() {
    const name = document.getElementById('new-item-name').value.trim();
    const qty = parseInt(document.getElementById('new-item-qty').value) || 1;
    const unitPrice = parseFloat(document.getElementById('new-item-unit-price').value) || 0;
    const totalPrice = parseFloat(document.getElementById('new-item-total').value) || 0;

    if (!name) {
        alert('Please enter item name');
        return;
    }

    if (qty <= 0 || unitPrice < 0 || totalPrice < 0) {
        alert('Please enter valid quantities and prices');
        return;
    }

    // Get next item ID
    const nextId = extractedItems.length > 0 ? Math.max(...extractedItems.map(i => i.id)) + 1 : 1;

    // Add to extractedItems
    extractedItems.push({
        id: nextId,
        name: name,
        quantity: qty,
        unit_price: unitPrice,
        total_price: totalPrice,
        assigned_to: null
    });

    // Re-render items
    displayExtractedItems(extractedItems);

    // Close modal
    document.getElementById('add-item-modal').classList.add('hidden');
    clearAddItemForm();

    console.log('Item added. Total items:', extractedItems.length);
});

function clearAddItemForm() {
    document.getElementById('new-item-name').value = '';
    document.getElementById('new-item-qty').value = '1';
    document.getElementById('new-item-unit-price').value = '';
    document.getElementById('new-item-total').value = '0.00';
}

// Auto-update Amount field when items change
function updateAmountFieldFromItems() {
    const total = extractedItems.reduce((sum, item) => sum + item.total_price, 0);
    document.getElementById('amount').value = total.toFixed(2);
    updateTotalDisplay();
}

// Override updateOCRTotal to also update Amount field
const originalUpdateOCRTotal = updateOCRTotal;
updateOCRTotal = function() {
    originalUpdateOCRTotal();
    updateAmountFieldFromItems();
};
</script>
@endsection
