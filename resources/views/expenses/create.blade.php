@extends('layouts.app')

@section('title', 'Add Expense to ' . $group->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Add Expense</h1>
        <p class="text-gray-600 mb-6">Create a new expense for <strong>{{ $group->name }}</strong></p>

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
                    <p class="text-sm font-semibold text-gray-700">Selected Files:</p>
                    <ul id="files" class="space-y-2"></ul>
                </div>

                <!-- OCR Processing Button -->
                <div id="ocr-section" class="mt-4 hidden">
                    <button
                        type="button"
                        id="process-ocr-btn"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold flex items-center gap-2"
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

                <!-- Extracted Items Section -->
                <div id="items-section" class="mt-6 hidden">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <h3 class="font-semibold text-green-900 mb-3">📦 Extracted Line Items</h3>
                        <p class="text-sm text-green-800 mb-4">Select items to assign to specific members. Unselected items will be split equally.</p>

                        <!-- Items Display -->
                        <div id="items-list" class="space-y-3 mb-4">
                            <!-- Populated by JavaScript -->
                        </div>

                        <!-- Add Item Button -->
                        <button
                            type="button"
                            id="add-item-btn"
                            class="w-full px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors font-semibold text-sm mb-4 border border-blue-300"
                        >
                            + Add Missing Item
                        </button>

                        <!-- OCR Confidence -->
                        <div id="ocr-warning" class="text-xs text-orange-600 mt-3 p-2 bg-orange-50 rounded border border-orange-200 hidden">
                            ⚠️ <span id="warning-text"></span>
                        </div>

                        <!-- Summary -->
                        <div class="mt-4 pt-4 border-t border-green-200">
                            <p class="text-sm">
                                <strong>Total from receipt:</strong>
                                <span id="ocr-total" class="font-semibold">0.00</span>
                                {{ $group->currency }}
                            </p>
                        </div>

                        <!-- Use Extracted Button -->
                        <button
                            type="button"
                            id="use-extracted-btn"
                            class="mt-4 w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold"
                        >
                            Use These Items & Auto-Split
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

function updateFileList() {
    const files = fileInput.files;
    const fileList = document.getElementById('file-list');
    const fileListUl = document.getElementById('files');
    const ocrSection = document.getElementById('ocr-section');

    fileListUl.innerHTML = '';

    if (files.length > 0) {
        fileList.classList.remove('hidden');
        ocrSection.classList.remove('hidden');
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

<!-- Tesseract.js for OCR -->
<script async src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.0/dist/tesseract.min.js"></script>

<script>
const members = {!! json_encode($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values()) !!};
let extractedItems = [];
let currentFile = null;

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

// Process OCR
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
        const file = files[0];
        const imageUrl = URL.createObjectURL(file);

        console.log('Starting OCR for file:', file.name);

        // Check if Tesseract is loaded
        if (typeof Tesseract === 'undefined') {
            throw new Error('Tesseract.js library not loaded. Please refresh the page.');
        }

        console.log('Tesseract library loaded successfully');

        // Initialize Tesseract worker with correct v5 API
        const { createWorker } = Tesseract;
        console.log('Creating Tesseract worker...');
        const worker = await createWorker();

        console.log('Worker created, starting recognition...');
        const result = await worker.recognize(imageUrl);
        const text = result.data.text;

        console.log('OCR text extracted:', text);

        await worker.terminate();

        // Parse receipt and extract items
        console.log('Parsing receipt...');
        extractedItems = parseReceipt(text);

        console.log('Items extracted:', extractedItems);

        if (extractedItems.length === 0) {
            alert('No items found in receipt. Please check the image quality and try again.');
            return;
        }

        // Display items
        displayExtractedItems(extractedItems);
        document.getElementById('items-section').classList.remove('hidden');

        console.log('Items displayed successfully');

    } catch (error) {
        console.error('OCR Error:', error);
        console.error('Error details:', error.message);
        alert('Error processing receipt: ' + error.message + '\n\nPlease check browser console for details.');
    } finally {
        btn.disabled = false;
        btnText.textContent = '🔍 Extract Line Items from Receipt';
        spinner.classList.add('hidden');
    }
});

// Parse receipt text and extract items
function parseReceipt(text) {
    const lines = text.split('\n').map(l => l.trim()).filter(l => l);
    const items = [];
    let total = 0;

    console.log('Parsing receipt text:');
    console.log('Lines:', lines);

    // Price pattern - more permissive
    const pricePattern = /(\d+[.,]\d{2})/;
    const quantityPattern = /\b[x×]\s*(\d+)\b/i;

    // Common receipt headers/footers to ignore
    const ignoreKeywords = ['welcome', 'thank', 'thanks', 'total', 'subtotal', 'net', 'cash', 'card',
                            'payment', 'paid', 'change', 'gst', 'vat', 'tax', 'discount', 'member',
                            'loyalty', 'phone', 'address', 'date', 'time', 'store', 'location',
                            'receipt', 'invoice', 'account', 'balance', 'till', 'cashier', 'items', 'qty'];

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

        // Skip lines with only keywords
        if (ignoreKeywords.some(kw => lineUpper === kw.toUpperCase())) {
            console.log('Skipping keyword line:', line);
            pendingName = null;
            continue;
        }

        // Find price in this line
        const priceMatch = line.match(pricePattern);

        if (priceMatch) {
            // This line has a price
            const priceStr = priceMatch[1];
            const price = parseFloat(priceStr.replace(',', '.'));

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
            // Remove single letters/codes at start
            name = name.replace(/^[a-z]\s+/i, '').trim();

            // Extract quantity
            const qtyMatch = line.match(quantityPattern);
            const quantity = qtyMatch ? parseInt(qtyMatch[1]) : 1;

            // Validate: name should have at least one letter
            const hasLetters = /[a-zA-Z]/.test(name);
            const isValidPrice = price > 0 && price < 100000; // Reasonable price range

            if (hasLetters && isValidPrice && name.length > 0) {
                console.log('Adding item:', name, quantity, price);
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
                console.log('Rejected item:', name, 'hasLetters:', hasLetters, 'isValidPrice:', isValidPrice);
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

        const itemDiv = document.createElement('div');
        itemDiv.className = 'p-4 bg-white border border-green-200 rounded-lg';
        itemDiv.id = `item-${item.id}`;

        // Display mode
        const displayHtml = `
            <div class="item-display">
                <div class="flex items-start gap-3 mb-3">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">${escapeHtml(item.name)}</p>
                        <p class="text-sm text-gray-600">
                            Qty: ${item.quantity} × ${getCurrencySymbol()} ${item.unit_price.toFixed(2)} =
                            <span class="font-semibold">${getCurrencySymbol()} ${item.total_price.toFixed(2)}</span>
                        </p>
                    </div>
                    <button type="button" class="edit-item-btn text-blue-600 hover:text-blue-700 font-semibold text-sm px-3 py-1 border border-blue-300 rounded hover:bg-blue-50" data-item-id="${item.id}">
                        ✏️ Edit
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <select class="item-member text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-green-500 flex-1" data-item-id="${item.id}">
                        <option value="">Not assigned</option>
                        ${members.map(m => `<option value="${m.id}" ${item.assigned_to == m.id ? 'selected' : ''}>${m.name}</option>`).join('')}
                    </select>
                    <button type="button" class="delete-item-btn text-red-600 hover:text-red-700 font-semibold px-2 py-1" data-item-id="${item.id}">
                        ✕ Delete
                    </button>
                </div>
            </div>
        `;

        // Edit mode
        const editHtml = `
            <div class="item-edit hidden">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Item Name</label>
                        <input type="text" class="edit-name w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${escapeHtml(item.name)}" />
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Qty</label>
                            <input type="number" class="edit-qty w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.quantity}" min="1" step="1" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Unit Price</label>
                            <input type="number" class="edit-unit-price w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.unit_price.toFixed(2)}" min="0" step="0.01" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Total</label>
                            <input type="number" class="edit-total-price w-full px-2 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" value="${item.total_price.toFixed(2)}" min="0" step="0.01" />
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

        itemDiv.innerHTML = displayHtml + editHtml;
        itemsList.appendChild(itemDiv);
    });

    updateOCRTotal();
    attachItemEventListeners();
}

// Attach event listeners for edit/delete/save
function attachItemEventListeners() {
    // Edit button
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemDiv = document.getElementById(`item-${itemId}`);
            itemDiv.querySelector('.item-display').classList.add('hidden');
            itemDiv.querySelector('.item-edit').classList.remove('hidden');
        });
    });

    // Cancel button
    document.querySelectorAll('.cancel-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemDiv = document.getElementById(`item-${itemId}`);
            itemDiv.querySelector('.item-display').classList.remove('hidden');
            itemDiv.querySelector('.item-edit').classList.add('hidden');
        });
    });

    // Real-time total calculation on quantity/price change
    document.querySelectorAll('.edit-qty, .edit-unit-price').forEach(input => {
        input.addEventListener('change', function() {
            const itemDiv = this.closest('.item-edit');
            const qtyInput = itemDiv.querySelector('.edit-qty');
            const unitPriceInput = itemDiv.querySelector('.edit-unit-price');
            const totalPriceInput = itemDiv.querySelector('.edit-total-price');

            const qty = parseInt(qtyInput.value) || 0;
            const unitPrice = parseFloat(unitPriceInput.value) || 0;
            const calculatedTotal = (qty * unitPrice).toFixed(2);

            totalPriceInput.value = calculatedTotal;
        });

        // Update receipt total as user types
        input.addEventListener('input', function() {
            const itemDiv = this.closest('.item-edit');
            const qtyInput = itemDiv.querySelector('.edit-qty');
            const unitPriceInput = itemDiv.querySelector('.edit-unit-price');
            const totalPriceInput = itemDiv.querySelector('.edit-total-price');

            const qty = parseInt(qtyInput.value) || 0;
            const unitPrice = parseFloat(unitPriceInput.value) || 0;
            const calculatedTotal = (qty * unitPrice).toFixed(2);

            totalPriceInput.value = calculatedTotal;
            updateOCRTotal();
        });
    });

    // Update total when total price is manually edited
    document.querySelectorAll('.edit-total-price').forEach(input => {
        input.addEventListener('input', updateOCRTotal);
    });

    // Save button
    document.querySelectorAll('.save-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemDiv = document.getElementById(`item-${itemId}`);

            const name = itemDiv.querySelector('.edit-name').value.trim();
            const qty = parseInt(itemDiv.querySelector('.edit-qty').value) || 1;
            const unitPrice = parseFloat(itemDiv.querySelector('.edit-unit-price').value) || 0;
            const totalPrice = parseFloat(itemDiv.querySelector('.edit-total-price').value) || 0;

            if (!name) {
                alert('Item name cannot be empty');
                return;
            }

            if (qty <= 0 || unitPrice < 0 || totalPrice < 0) {
                alert('Please enter valid quantities and prices');
                return;
            }

            // Update item in extractedItems array
            const item = extractedItems.find(i => i.id == itemId);
            if (item) {
                item.name = name;
                item.quantity = qty;
                item.unit_price = unitPrice;
                item.total_price = totalPrice;
            }

            // Re-render to update display
            displayExtractedItems(extractedItems);

            // Auto-update splits if in custom split mode
            if (document.getElementById('split_type').value === 'custom') {
                updateSplitsFromItems();
            }
        });
    });

    // Delete button
    document.querySelectorAll('.delete-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            if (confirm('Delete this item?')) {
                extractedItems = extractedItems.filter(i => i.id != itemId);
                displayExtractedItems(extractedItems);

                // Auto-update splits if in custom split mode
                if (document.getElementById('split_type').value === 'custom') {
                    updateSplitsFromItems();
                }
            }
        });
    });

    // Member assignment dropdown - save and update splits
    document.querySelectorAll('.item-member').forEach(select => {
        select.addEventListener('change', function() {
            const itemId = this.dataset.itemId;
            const memberId = this.value ? parseInt(this.value) : null;

            // Update item's assigned_to in extractedItems
            const item = extractedItems.find(i => i.id == itemId);
            if (item) {
                item.assigned_to = memberId;
            }

            // Auto-update splits if already in split mode
            const splitType = document.getElementById('split_type').value;
            const customSplitsDiv = document.getElementById('custom-splits');

            if (splitType === 'custom' && customSplitsDiv && !customSplitsDiv.classList.contains('hidden')) {
                updateSplitsFromItems();
            }
        });
    });
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
