@extends('layouts.app')

@section('title', 'Add Expense with OCR to ' . $group->name)

@section('content')
<div class="w-full bg-gradient-to-b from-blue-50 via-white to-white min-h-screen">
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-2">
                    🔍 Add Expense with OCR
                </h1>
                <p class="text-lg text-gray-600">
                    to <strong>{{ $group->name }}</strong> · {{ $group->members->count() }} members
                </p>
                @if($canUseOCR)
                    <p class="text-sm text-green-600 mt-2">✓ OCR Scans Remaining: {{ $remainingOCRScans }} ({{ $planName }})</p>
                @endif
            </div>

            <!-- Warning if OCR not available -->
            @if(!$canUseOCR)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-yellow-800">
                        <strong>Note:</strong> Your plan has reached its OCR limit. <a href="{{ route('groups.expenses.create', $group) }}" class="underline font-semibold">Use standard form</a>
                    </p>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
                <!-- Step Indicator -->
                <div class="mb-8 bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <h3 class="text-sm font-bold text-gray-600 uppercase mb-4">OCR Expense Flow</h3>
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col items-center" id="step-1">
                            <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm mb-1">1</div>
                            <span class="text-xs font-semibold text-gray-700">Scan Receipt</span>
                        </div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2 mb-6"></div>
                        <div class="flex flex-col items-center" id="step-2">
                            <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm mb-1">2</div>
                            <span class="text-xs font-semibold text-gray-700">Review Data</span>
                        </div>
                        <div class="flex-1 h-1 bg-gray-300 mx-2 mb-6"></div>
                        <div class="flex flex-col items-center" id="step-3">
                            <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold text-sm mb-1">3</div>
                            <span class="text-xs font-semibold text-gray-700">Save Expense</span>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Upload Receipt -->
                <div id="step-1-content">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded">
                        <p class="text-blue-900 text-sm">
                            <strong>📸 Step 1:</strong> Upload a clear photo of your receipt. Our OCR will automatically extract the items and total amount.
                        </p>
                    </div>

                    <!-- Receipt Upload Area -->
                    <div class="mb-8">
                        <label class="block text-sm font-semibold text-gray-700 mb-4">Receipt Image</label>
                        <div class="relative border-2 border-dashed border-blue-300 rounded-lg p-8 text-center cursor-pointer bg-blue-50 hover:bg-blue-100 transition" id="receipt-upload-area">
                            <input type="file" id="receipt-input" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                            <div id="upload-icon">
                                <svg class="mx-auto h-12 w-12 text-blue-500 mb-2" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h24a4 4 0 004-4V20m-14-8v20m-8-8l8 8 8-8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                                <p class="text-lg font-semibold text-gray-700 mb-1">Click to upload or drag and drop</p>
                                <p class="text-sm text-gray-500">PNG, JPG, GIF or BMP (max 20MB)</p>
                            </div>
                            <div id="upload-preview" style="display: none;" class="mt-4">
                                <img id="preview-image" class="max-h-64 mx-auto rounded-lg mb-2" />
                                <p id="preview-text" class="text-sm text-gray-600"></p>
                            </div>
                        </div>
                        <input type="hidden" id="receipt-image-input" name="receipt_image" />
                    </div>

                    <!-- Processing Status -->
                    <div id="processing-status" style="display: none;" class="mb-8 p-4 bg-blue-100 border border-blue-300 rounded-lg">
                        <div class="flex items-center">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-3"></div>
                            <span class="text-blue-700 text-sm font-semibold">Processing receipt with OCR...</span>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div id="ocr-error" style="display: none;" class="mb-8 p-4 bg-red-100 border border-red-300 rounded-lg text-red-700"></div>

                    <!-- Extract Button -->
                    <button type="button" id="extract-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Extract Data with OCR
                    </button>
                </div>

                <!-- Step 2: Review and Edit Extracted Data -->
                <div id="step-2-content" style="display: none;">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 rounded">
                        <p class="text-green-900 text-sm">
                            <strong>✓ Step 2:</strong> Review the extracted data. Edit any fields that need adjustment.
                        </p>
                    </div>

                    <form id="expense-form" action="{{ route('groups.expenses-ocr.store', $group) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Hidden fields for OCR data -->
                        <input type="hidden" id="items-json" name="items_json" />
                        <input type="hidden" id="ocr-confidence" name="ocr_confidence" />

                        <!-- Vendor/Store Name -->
                        <div>
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Vendor/Store Name</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="{{ old('title') }}"
                                class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="e.g., Whole Foods, Target"
                                required
                            />
                            @error('title')
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
                                value="{{ old('date', now()->format('Y-m-d')) }}"
                                class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            />
                            @error('date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">Total Amount</label>
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
                                    class="w-full pl-8 pr-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <select
                                id="category"
                                name="category"
                                class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Select a category</option>
                                <option value="Accommodation" {{ old('category') === 'Accommodation' ? 'selected' : '' }}>🏨 Accommodation</option>
                                <option value="Food & Dining" {{ old('category') === 'Food & Dining' ? 'selected' : '' }}>🍽️ Food & Dining</option>
                                <option value="Groceries" {{ old('category') === 'Groceries' ? 'selected' : '' }}>🛒 Groceries</option>
                                <option value="Transport" {{ old('category') === 'Transport' ? 'selected' : '' }}>🚗 Transport</option>
                                <option value="Activities" {{ old('category') === 'Activities' ? 'selected' : '' }}>🎭 Activities</option>
                                <option value="Shopping" {{ old('category') === 'Shopping' ? 'selected' : '' }}>🛍️ Shopping</option>
                                <option value="Utilities & Services" {{ old('category') === 'Utilities & Services' ? 'selected' : '' }}>🔧 Utilities & Services</option>
                                <option value="Fees & Charges" {{ old('category') === 'Fees & Charges' ? 'selected' : '' }}>💳 Fees & Charges</option>
                                <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>📌 Other</option>
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description (Optional)</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Optional: Add any notes about this expense"
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Split Type -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">How to split this expense?</label>
                            <div class="space-y-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="split_type" value="equal" checked class="form-radio h-4 w-4 text-blue-600" onchange="handleSplitTypeChange()">
                                    <span class="ml-3 text-gray-700">Equal split among all members</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="split_type" value="custom" class="form-radio h-4 w-4 text-blue-600" onchange="handleSplitTypeChange()">
                                    <span class="ml-3 text-gray-700">Custom split</span>
                                </label>
                            </div>
                            @error('split_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Custom Splits Section -->
                        <div id="custom-splits" style="display: none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Enter amount for each member:</label>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach($members as $member)
                                    <div class="flex items-center gap-4">
                                        <label class="flex-1 text-sm text-gray-700">
                                            {{ $member->user ? $member->user->name : $member->contact->name }}
                                        </label>
                                        <div class="relative w-32">
                                            <span class="absolute right-4 top-2 text-gray-600">
                                                {{ $group->currency === 'USD' ? '$' : ($group->currency === 'EUR' ? '€' : ($group->currency === 'GBP' ? '£' : '₹')) }}
                                            </span>
                                            <input
                                                type="number"
                                                name="splits[{{ $member->id }}]"
                                                step="0.01"
                                                min="0"
                                                class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="0.00"
                                            />
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Extracted Items Display -->
                        <div id="items-section" style="display: none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Extracted Items</label>
                            <div id="items-display" class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto border border-gray-200">
                                <p class="text-gray-500 text-sm">No items extracted</p>
                            </div>
                        </div>

                        <!-- OCR Confidence Display -->
                        <div id="confidence-section" style="display: none;" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-gray-700">
                                <strong>OCR Confidence:</strong> <span id="confidence-value">0%</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Higher confidence means more accurate extraction</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-6">
                            <button type="button" onclick="backToUpload()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg transition">
                                ← Back
                            </button>
                            <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition">
                                ✓ Save Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Link back to standard form -->
            <div class="mt-8 text-center">
                <p class="text-gray-600 text-sm">
                    Prefer the standard form? <a href="{{ route('groups.expenses.create', $group) }}" class="text-blue-600 hover:underline font-semibold">Use classic add expense</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    const receiptInput = document.getElementById('receipt-input');
    const receiptUploadArea = document.getElementById('receipt-upload-area');
    const uploadIcon = document.getElementById('upload-icon');
    const uploadPreview = document.getElementById('upload-preview');
    const previewImage = document.getElementById('preview-image');
    const previewText = document.getElementById('preview-text');
    const extractBtn = document.getElementById('extract-btn');
    const processingStatus = document.getElementById('processing-status');
    const ocrError = document.getElementById('ocr-error');
    const itemsInput = document.getElementById('items-json');
    const confidenceInput = document.getElementById('ocr-confidence');
    const customSplitsDiv = document.getElementById('custom-splits');
    const itemsSection = document.getElementById('items-section');
    const confidenceSection = document.getElementById('confidence-section');
    const itemsDisplay = document.getElementById('items-display');
    const confidenceValue = document.getElementById('confidence-value');
    let selectedFile = null;

    // Handle file selection
    receiptInput.addEventListener('change', function(e) {
        handleFileSelect(e.target.files[0]);
    });

    // Drag and drop
    receiptUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        receiptUploadArea.classList.add('bg-blue-200');
    });

    receiptUploadArea.addEventListener('dragleave', () => {
        receiptUploadArea.classList.remove('bg-blue-200');
    });

    receiptUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        receiptUploadArea.classList.remove('bg-blue-200');
        handleFileSelect(e.dataTransfer.files[0]);
    });

    function handleFileSelect(file) {
        if (!file) return;

        // Validate file
        if (!['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'].includes(file.type)) {
            showOcrError('Invalid file type. Please upload an image.');
            return;
        }

        if (file.size > 20 * 1024 * 1024) {
            showOcrError('File is too large. Maximum size is 20MB.');
            return;
        }

        selectedFile = file;
        extractBtn.disabled = false;

        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
            previewText.textContent = file.name;
            uploadIcon.style.display = 'none';
            uploadPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Extract button click
    extractBtn.addEventListener('click', async function() {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('receipt_image', selectedFile);

        processingStatus.style.display = 'block';
        extractBtn.disabled = true;
        ocrError.style.display = 'none';

        try {
            const response = await fetch('{{ route("groups.expenses-ocr.extract", $group) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to extract data');
            }

            // Fill form with extracted data
            if (data.data.vendor) {
                document.getElementById('title').value = data.data.vendor;
            }

            if (data.data.date) {
                document.getElementById('date').value = data.data.date;
            }

            if (data.data.total_amount) {
                document.getElementById('amount').value = data.data.total_amount.toFixed(2);
            }

            // Store items
            if (data.data.items && data.data.items.length > 0) {
                itemsInput.value = JSON.stringify(data.data.items);
                displayItems(data.data.items);
                itemsSection.style.display = 'block';
            }

            // Store confidence
            if (data.data.confidence) {
                confidenceInput.value = data.data.confidence;
                confidenceValue.textContent = Math.round(data.data.confidence * 100) + '%';
                confidenceSection.style.display = 'block';
            }

            // Move to step 2
            moveToStep2();

        } catch (error) {
            showOcrError(error.message);
        } finally {
            processingStatus.style.display = 'none';
            extractBtn.disabled = false;
        }
    });

    function displayItems(items) {
        if (!items || items.length === 0) {
            itemsDisplay.innerHTML = '<p class="text-gray-500 text-sm">No items extracted</p>';
            return;
        }

        const html = items.map(item => `
            <div class="flex justify-between items-center py-2 border-b border-gray-200 last:border-b-0">
                <span class="text-sm text-gray-700">${escapeHtml(item.description)}</span>
                <span class="text-sm font-semibold text-gray-900">${item.amount ? '$' + item.amount.toFixed(2) : 'N/A'}</span>
            </div>
        `).join('');

        itemsDisplay.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showOcrError(message) {
        ocrError.textContent = message;
        ocrError.style.display = 'block';
    }

    function moveToStep2() {
        document.getElementById('step-1-content').style.display = 'none';
        document.getElementById('step-2-content').style.display = 'block';

        // Update step indicator
        document.getElementById('step-1').querySelector('div').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('step-1').querySelector('div').classList.add('bg-green-600', 'text-white');
        document.getElementById('step-2').querySelector('div').classList.remove('bg-gray-300', 'text-gray-600');
        document.getElementById('step-2').querySelector('div').classList.add('bg-blue-600', 'text-white');

        // Scroll to form
        document.querySelector('.max-w-4xl').scrollIntoView({ behavior: 'smooth' });
    }

    function backToUpload() {
        document.getElementById('step-1-content').style.display = 'block';
        document.getElementById('step-2-content').style.display = 'none';

        // Reset step indicator
        document.getElementById('step-1').querySelector('div').classList.add('bg-blue-600', 'text-white');
        document.getElementById('step-1').querySelector('div').classList.remove('bg-green-600');
        document.getElementById('step-2').querySelector('div').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('step-2').querySelector('div').classList.add('bg-gray-300', 'text-gray-600');
    }

    function handleSplitTypeChange() {
        const splitType = document.querySelector('input[name="split_type"]:checked').value;
        customSplitsDiv.style.display = splitType === 'custom' ? 'block' : 'none';
    }
</script>
@endsection
