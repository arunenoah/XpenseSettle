@extends('layouts.app')

@section('title', 'Edit ' . $group->name)

@section('content')
<!-- Group Breadcrumb -->
<x-group-breadcrumb :group="$group" />

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Edit Group</h1>

        <form action="{{ route('groups.update', $group) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Group Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Group Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $group->name) }}"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('name') ? 'border-red-500' : '' }}"
                    required
                />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('description') ? 'border-red-500' : '' }}"
                    placeholder="Optional: Add details about this expense group"
                >{{ old('description', $group->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Currency -->
            <div>
                <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">Currency</label>
                <select
                    id="currency"
                    name="currency"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('currency') ? 'border-red-500' : '' }}"
                >
                    <option value="USD" {{ old('currency', $group->currency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                    <option value="EUR" {{ old('currency', $group->currency) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                    <option value="GBP" {{ old('currency', $group->currency) === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                    <option value="INR" {{ old('currency', $group->currency) === 'INR' ? 'selected' : '' }}>INR (₹)</option>
                    <option value="AUD" {{ old('currency', $group->currency) === 'AUD' ? 'selected' : '' }}>AUD (A$)</option>
                    <option value="CAD" {{ old('currency', $group->currency) === 'CAD' ? 'selected' : '' }}>CAD (C$)</option>
                </select>
                @error('currency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Group Info -->
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-sm text-gray-700"><strong>Created:</strong> {{ $group->created_at->format('M d, Y') }}</p>
                <p class="text-sm text-gray-700"><strong>Members:</strong> {{ $group->members()->count() }}</p>
                <p class="text-sm text-gray-700"><strong>Expenses:</strong> {{ $group->expenses()->count() }}</p>
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
                    href="{{ route('groups.show', $group) }}"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-semibold text-center"
                >
                    Cancel
                </a>
            </div>
        </form>

        <!-- Delete Section -->
        <div class="mt-8 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="font-semibold text-red-900 mb-2">Danger Zone</h3>
            <p class="text-sm text-red-800 mb-4">Once you delete a group, there is no going back. Please be certain.</p>
            <form action="{{ route('groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold"
                >
                    Delete Group
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Floating Action Buttons -->
<x-group-fabs :group="$group" />

@endsection
