@extends('layouts.app')

@section('title', 'Create Group')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Create New Group</h1>

        <form action="{{ route('groups.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Group Icon -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-3">Pick a Fun Icon! 🎨</label>
                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                    @php
                        $icons = ['🏠', '✈️', '🍕', '🎉', '💼', '🎓', '🏖️', '🍔', '🎮', '⚽', '🎸', '🎬', '📚', '🍺', '☕', '🎂', '🚗', '🏃', '🎨', '💰'];
                    @endphp
                    @foreach($icons as $icon)
                        <label class="cursor-pointer">
                            <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only" {{ old('icon', '🎉') === $icon ? 'checked' : '' }}>
                            <div class="text-4xl p-3 rounded-xl bg-white border-2 border-gray-200 hover:border-purple-400 hover:bg-purple-50 peer-checked:border-purple-600 peer-checked:bg-purple-100 peer-checked:shadow-lg transition-all transform hover:scale-110 peer-checked:scale-110 flex items-center justify-center">
                                {{ $icon }}
                            </div>
                        </label>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-gray-600 text-center sm:text-left">
                    👆 Click any icon to select it
                </p>
            </div>

            <!-- Group Name -->
            <div>
                <label for="name" class="block text-sm font-bold text-gray-700 mb-2">Squad Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    class="w-full px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 {{ $errors->has('name') ? 'border-red-500' : '' }}"
                    placeholder="e.g., Beach Squad, Roomies, Party Crew"
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
                >{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Optional</p>
            </div>

            <!-- Currency -->
            <div>
                <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">Currency</label>
                <select
                    id="currency"
                    name="currency"
                    class="w-full px-4 py-2 sm:py-3 border border-gray-300 rounded-lg text-base focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $errors->has('currency') ? 'border-red-500' : '' }}"
                >
                    <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                    <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                    <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                    <option value="INR" {{ old('currency') === 'INR' ? 'selected' : '' }}>INR (₹)</option>
                    <option value="AUD" {{ old('currency') === 'AUD' ? 'selected' : '' }}>AUD (A$)</option>
                    <option value="CAD" {{ old('currency') === 'CAD' ? 'selected' : '' }}>CAD (C$)</option>
                </select>
                @error('currency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 pt-6">
                <button
                    type="submit"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all transform hover:scale-105 font-bold shadow-lg"
                >
                    🎉 Create Squad!
                </button>
                <a
                    href="{{ route('dashboard') }}"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-900 rounded-xl hover:bg-gray-300 transition-colors font-semibold text-center"
                >
                    Cancel
                </a>
            </div>
        </form>

        <!-- Help Text -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-semibold text-blue-900 mb-2">Tips for Creating a Group</h3>
            <ul class="list-disc pl-5 space-y-1 text-sm text-blue-800">
                <li>Use descriptive names that help members understand the purpose</li>
                <li>Add a description to clarify what expenses will be tracked</li>
                <li>Choose the correct currency for all transactions</li>
                <li>You'll be able to add members after creating the group</li>
            </ul>
        </div>
    </div>
</div>
@endsection
