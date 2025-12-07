@props(['group'])

<!-- Mobile Floating Action Buttons -->
<div class="fixed bottom-6 right-6 sm:hidden z-40 flex flex-col gap-3">
    <!-- Add Advance FAB -->
    <button onclick="openAdvanceModal()" class="inline-flex justify-center items-center w-14 h-14 bg-amber-600 text-white rounded-full hover:bg-amber-700 transition-all transform hover:scale-110 font-bold shadow-lg" title="Add Advance">
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
