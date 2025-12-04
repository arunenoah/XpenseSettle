@extends('layouts.app')

@section('title', 'Groups')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">My Groups</h1>
            <p class="mt-2 text-gray-600">Manage and view all your expense groups</p>
        </div>
        <a href="{{ route('groups.create') }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Group
        </a>
    </div>

    <!-- Groups Grid -->
    @if($groups->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($groups as $group)
                <a href="{{ route('groups.dashboard', $group) }}" class="group">
                    <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-lg transition-shadow h-full flex flex-col">
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 truncate">
                                    {{ $group->name }}
                                </h3>
                            </div>
                            <span class="inline-block px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-semibold whitespace-nowrap">
                                {{ $group->currency }}
                            </span>
                        </div>

                        <!-- Description -->
                        @if($group->description)
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $group->description }}</p>
                        @endif

                        <!-- Stats -->
                        <div class="mt-auto space-y-2 pt-4 border-t border-gray-200">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Members</span>
                                <span class="font-semibold text-gray-900">{{ $group->members()->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Expenses</span>
                                <span class="font-semibold text-gray-900">{{ $group->expenses()->count() }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Created</span>
                                <span class="text-gray-500">{{ $group->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mt-4 pt-4 border-t border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-xs font-bold text-white">
                                    {{ strtoupper(substr($group->creator->name, 0, 1)) }}
                                </div>
                                <span class="text-sm text-gray-600 truncate">{{ $group->creator->name }}</span>
                            </div>
                            @if($group->isAdmin(auth()->user()))
                                <span class="inline-block px-2 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded whitespace-nowrap">Admin</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $groups->links() }}
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 sm:p-8 text-center">
            <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10a3 3 0 11-6 0 3 3 0 016 0zM6 20h12a6 6 0 00-6-6 6 6 0 00-6 6z" />
            </svg>
            <h3 class="text-lg sm:text-xl font-semibold text-gray-900">No groups yet</h3>
            <p class="mt-2 text-gray-600">Create your first group to start tracking expenses</p>
            <a href="{{ route('groups.create') }}" class="mt-4 inline-flex items-center px-4 py-2 sm:px-6 sm:py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                Create Group
            </a>
        </div>
    @endif
</div>
@endsection
