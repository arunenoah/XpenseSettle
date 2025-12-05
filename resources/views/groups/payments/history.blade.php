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
                Settlement Summary
            </h1>
            <p class="mt-2 text-gray-600">{{ $group->name }} • {{ auth()->user()->name }}</p>
        </div>
    </div>

    <!-- Settlement Summary Cards -->
    @php
        $totalOwed = collect($settlement)->filter(fn($s) => $s['net_amount'] > 0)->sum('amount');
        $totalOwe = collect($settlement)->filter(fn($s) => $s['net_amount'] < 0)->sum('amount');
        $totalAdvances = collect($settlement)->sum('advance');
        $netBalance = $totalOwe - $totalOwed;
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <!-- You Owe -->
        <div class="bg-gradient-to-br from-red-50 to-pink-50 rounded-xl p-5 border-2 border-red-200 shadow-sm">
            <p class="text-sm font-bold text-red-700 flex items-center gap-2 mb-2">
                <span class="text-xl">😬</span>
                <span>You Owe</span>
            </p>
            <p class="text-3xl font-black text-red-600">${{ number_format($totalOwed, 2) }}</p>
        </div>

        <!-- They Owe You -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border-2 border-green-200 shadow-sm">
            <p class="text-sm font-bold text-green-700 flex items-center gap-2 mb-2">
                <span class="text-xl">🤑</span>
                <span>They Owe You</span>
            </p>
            <p class="text-3xl font-black text-green-600">${{ number_format($totalOwe, 2) }}</p>
        </div>

        <!-- Advances Paid -->
        <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl p-5 border-2 border-cyan-200 shadow-sm">
            <p class="text-sm font-bold text-cyan-700 flex items-center gap-2 mb-2">
                <span class="text-xl">💰</span>
                <span>Advances Paid</span>
            </p>
            <p class="text-3xl font-black text-cyan-600">${{ number_format($totalAdvances, 2) }}</p>
        </div>

        <!-- Net Balance -->
        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl p-5 border-2 border-purple-200 shadow-sm">
            <p class="text-sm font-bold text-purple-700 flex items-center gap-2 mb-2">
                <span class="text-xl">⚖️</span>
                <span>Net Balance</span>
            </p>
            <p class="text-3xl font-black {{ $netBalance > 0 ? 'text-green-600' : ($netBalance < 0 ? 'text-red-600' : 'text-gray-600') }}">
                {{ $netBalance > 0 ? '✓ +' : ($netBalance < 0 ? '✗ ' : '') }}${{ number_format(abs($netBalance), 2) }}
            </p>
            <p class="text-xs text-gray-600 mt-1">
                {{ $netBalance > 0 ? 'you will receive' : ($netBalance < 0 ? 'you will owe' : 'settled') }}
            </p>
        </div>
    </div>

    <!-- Settlement Breakdown -->
    @if($settlement->count() > 0)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-4 sm:px-6 py-6 border-b-2 border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <span class="text-3xl">💳</span>
                    <span>Who Owes What</span>
                </h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($settlement as $item)
                    @php
                        $isOwed = $item['net_amount'] > 0;
                        $finalAmount = $isOwed ? ($item['amount'] - $item['advance']) : $item['amount'];
                    @endphp
                    <div class="p-6 hover:bg-gray-50 transition-all">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <!-- Person Info -->
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full {{ $isOwed ? 'bg-gradient-to-br from-red-400 to-pink-400' : 'bg-gradient-to-br from-green-400 to-emerald-400' }} flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr($item['user']->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900 text-lg">{{ $item['user']->name }}</p>
                                    <p class="text-sm text-gray-600">
                                        @if($isOwed)
                                            You owe them
                                        @else
                                            They owe you
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="text-right">
                                <!-- Final Amount -->
                                <p class="text-3xl font-black {{ $isOwed ? 'text-red-600' : 'text-green-600' }}">
                                    ${{ number_format($finalAmount, 2) }}
                                </p>

                                <!-- Advance Details -->
                                @if($item['advance'] > 0)
                                    <div class="mt-2 space-y-1">
                                        <p class="text-xs {{ $isOwed ? 'text-red-600' : 'text-green-600' }} font-semibold">
                                            💰 Advance: -${{ number_format($item['advance'], 2) }}
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            Original: ${{ number_format($item['amount'], 2) }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Status Badge -->
                                <span class="inline-block mt-2 px-3 py-1 {{ $isOwed ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }} text-xs font-bold rounded-full">
                                    {{ $isOwed ? '😬 You Owe' : '🤑 They Owe' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <!-- No Settlement -->
        <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-8 text-center border-2 border-blue-200">
            <p class="text-6xl mb-4">✨</p>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">All Settled!</h2>
            <p class="text-gray-600">You have no outstanding balances in {{ $group->name }}.</p>
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
