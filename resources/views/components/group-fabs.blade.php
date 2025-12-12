@props(['group', 'showPdfExport' => false])

<!-- Mobile Floating Action Buttons -->
<div class="fixed bottom-6 right-6 sm:hidden z-40 flex flex-col gap-3">
    @if($showPdfExport)
    <!-- Export PDF FAB -->
    <a href="{{ route('groups.payments.export-pdf', $group) }}" 
       download
       class="inline-flex justify-center items-center w-14 h-14 bg-red-600 text-white rounded-full hover:bg-red-700 active:bg-red-800 transition-all transform hover:scale-110 active:scale-95 font-bold shadow-lg" 
       title="Export Statement PDF">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    </a>
    @endif
    <!-- Add Advance FAB -->
    <button data-open-advance-modal="true" class="inline-flex justify-center items-center w-14 h-14 bg-amber-600 text-white rounded-full hover:bg-amber-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Advance">
        <span class="text-2xl">💰</span>
    </button>
    <!-- View Members FAB -->
    <a href="{{ route('groups.members', $group) }}" class="inline-flex justify-center items-center w-14 h-14 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="View Members">
        <span class="text-2xl">👥</span>
    </a>
    <!-- Add Expense FAB -->
    <a href="{{ route('groups.expenses.create', $group) }}" class="inline-flex justify-center items-center w-14 h-14 bg-green-600 text-white rounded-full hover:bg-green-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Expense">
        <span class="text-2xl">+</span>
    </a>
</div>
