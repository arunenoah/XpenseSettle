@props(['group'])

<!-- Group Breadcrumb Navigation -->
<div class="px-4 sm:px-6 lg:px-8 py-3 bg-gray-50 border-b border-gray-200">
    <div class="max-w-7xl mx-auto flex items-center gap-2 text-sm">
        <a href="{{ route('groups.index') }}" class="text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
            <span>👥</span>
            <span>All Groups</span>
        </a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700 font-medium">{{ $group->name }}</span>
    </div>
</div>
