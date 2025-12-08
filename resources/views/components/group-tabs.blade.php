@props(['group', 'active' => 'dashboard'])

<!-- Mobile-Responsive Group Navigation Tabs -->
<div class="px-4 sm:px-6 lg:px-8 bg-white border-b border-gray-200 sticky top-16 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto flex gap-1 sm:gap-8 overflow-x-auto scrollbar-hide">

        <!-- All Groups Link -->
        <a href="{{ route('groups.index') }}"
           title="All Groups"
           class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 border-transparent text-gray-600 hover:text-gray-900 font-semibold text-sm whitespace-nowrap transition-colors">
            <span class="text-lg sm:text-base">📋</span>
            <span class="hidden sm:inline">All Groups</span>
        </a>

        <!-- Dashboard Tab -->
        <a href="{{ route('groups.dashboard', $group) }}"
           title="Dashboard"
           class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 {{ $active === 'dashboard' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }} font-semibold text-sm whitespace-nowrap transition-colors">
            <span class="text-lg sm:text-base">📊</span>
            <span class="hidden sm:inline">Dashboard</span>
        </a>

        <!-- History Tab -->
        <a href="{{ route('groups.payments.history', $group) }}"
           title="Payment History"
           class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 {{ $active === 'history' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }} font-semibold text-sm whitespace-nowrap transition-colors">
            <span class="text-lg sm:text-base">📜</span>
            <span class="hidden sm:inline">History</span>
        </a>

        <!-- Members Tab -->
        <a href="{{ route('groups.members', $group) }}"
           title="Members"
           class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 {{ $active === 'members' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }} font-semibold text-sm whitespace-nowrap transition-colors">
            <span class="text-lg sm:text-base">👥</span>
            <span class="hidden sm:inline">Members</span>
        </a>

        <!-- All Expenses Tab -->
        <a href="#"
           onclick="showExpensesModal(); return false;"
           title="All Expenses"
           class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 {{ $active === 'expenses' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }} font-semibold text-sm whitespace-nowrap transition-colors cursor-pointer">
            <span class="text-lg sm:text-base">📋</span>
            <span class="hidden sm:inline">All Expenses</span>
        </a>

        <!-- Settings Tab (Admin Only) -->
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}"
               title="Settings"
               class="flex items-center gap-2 px-2 sm:px-4 py-4 border-b-2 {{ $active === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }} font-semibold text-sm whitespace-nowrap transition-colors">
                <span class="text-lg sm:text-base">⚙️</span>
                <span class="hidden sm:inline">Settings</span>
            </a>
        @endif

    </div>
</div>
