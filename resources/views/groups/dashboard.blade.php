@extends('layouts.app')

@section('title', $group->name)

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="space-y-6 sm:space-y-8">
        <!-- Navigation Menu -->
        <div class="bg-white rounded-xl shadow-md p-2 flex gap-2 overflow-x-auto">
        <a href="{{ route('groups.dashboard', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg font-bold whitespace-nowrap text-base shadow-lg">
            <span class="text-xl">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('groups.members', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">👥</span>
            <span>Members</span>
        </a>
        <a href="{{ route('groups.expenses.create', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
            <span class="text-xl">💸</span>
            <span>Add Expense</span>
        </a>
        @if($group->isAdmin(auth()->user()))
            <a href="{{ route('groups.edit', $group) }}" class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-900 hover:bg-gray-200 rounded-lg font-bold whitespace-nowrap transition-all text-base">
                <span class="text-xl">✏️</span>
                <span>Settings</span>
            </a>
        @endif
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-br from-purple-100 via-pink-100 to-blue-100 rounded-3xl shadow-xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-5xl sm:text-6xl">{{ $group->icon ?? '🎉' }}</span>
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $group->name }}</h1>
                        @if($group->description)
                            <p class="mt-1 text-gray-700 font-medium">{{ $group->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <span class="px-3 py-1 bg-white rounded-full text-sm font-bold text-purple-600 shadow-sm">
                        💰 {{ $group->currency }}
                    </span>
                    <span class="px-3 py-1 bg-white rounded-full text-sm font-bold text-blue-600 shadow-sm">
                        👥 {{ $group->members->count() }} squad members
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <a href="{{ route('groups.expenses.create', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all transform hover:scale-105 font-bold shadow-lg">
                    <span class="text-xl mr-2">💸</span>
                    Add Expense
                </a>
                @if($group->isAdmin(auth()->user()))
                    <a href="{{ route('groups.edit', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-orange-400 to-pink-400 text-white rounded-xl hover:from-orange-500 hover:to-pink-500 transition-all transform hover:scale-105 font-bold shadow-lg">
                        <span class="text-xl mr-2">✏️</span>
                        Edit
                    </a>
                    <a href="{{ route('groups.members', $group) }}" class="inline-flex justify-center items-center px-4 py-2 sm:px-6 sm:py-3 bg-gradient-to-r from-blue-400 to-cyan-400 text-white rounded-xl hover:from-blue-500 hover:to-cyan-500 transition-all transform hover:scale-105 font-bold shadow-lg">
                        <span class="text-xl mr-2">👥</span>
                        Members
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- User's Balance Card -->
    <div class="bg-gradient-to-br from-yellow-50 via-orange-50 to-red-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-xl sm:text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">💰</span>
            <span>Your Money Situation</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-md border-2 border-red-200">
                <p class="text-sm font-bold text-red-700 flex items-center gap-2 mb-2">
                    <span class="text-xl">😬</span>
                    <span>You Owe</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black text-red-600">
                    @if(collect($settlement['i_owe'])->sum('amount') > 0)
                        ${{ number_format(collect($settlement['i_owe'])->sum('amount'), 2) }}
                    @else
                        $0.00
                    @endif
                </p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-md border-2 border-green-200">
                <p class="text-sm font-bold text-green-700 flex items-center gap-2 mb-2">
                    <span class="text-xl">🤑</span>
                    <span>They Owe You</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black text-green-600 mb-3">
                    @if(count($settlement['owes_me']) > 0)
                        ${{ number_format(collect($settlement['owes_me'])->sum('amount'), 2) }}
                    @else
                        $0.00
                    @endif
                </p>
                @if(count($settlement['owes_me']) > 0)
                    <div class="text-xs text-gray-600 max-h-32 overflow-y-auto">
                        @foreach($settlement['owes_me'] as $item)
                            <div class="mb-1 pb-1 border-b border-gray-100 last:border-b-0">
                                <p class="font-semibold text-gray-900">{{ $item['from_user']->name }}</p>
                                <p class="text-xs text-gray-500">{{ $item['expense']->title }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @php
                $netBalance = collect($settlement['owes_me'])->sum('amount') - collect($settlement['i_owe'])->sum('amount');
            @endphp
            <div class="bg-white rounded-xl p-4 shadow-md border-2 {{ $netBalance >= 0 ? 'border-green-200' : 'border-orange-200' }}">
                <p class="text-sm font-bold {{ $netBalance >= 0 ? 'text-green-700' : 'text-orange-700' }} flex items-center gap-2 mb-2">
                    <span class="text-xl">{{ $netBalance >= 0 ? '✅' : '⚠️' }}</span>
                    <span>Net Balance</span>
                </p>
                <p class="text-3xl sm:text-4xl font-black {{ $netBalance >= 0 ? 'text-green-600' : 'text-orange-600' }}">
                    {{ $netBalance >= 0 ? '+' : '' }}${{ number_format($netBalance, 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Analytics -->
    <div class="bg-gradient-to-br from-cyan-50 via-blue-50 to-indigo-50 rounded-2xl shadow-lg p-4">
        <h2 class="text-lg font-black text-gray-900 mb-3 flex items-center gap-2">
            <span class="text-2xl">📊</span>
            <span>Group Analytics</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-gray-600 mb-1">💰 Total</p>
                <p class="text-xl font-black text-gray-900">${{ number_format(collect($settlement['i_owe'])->sum('amount') + collect($settlement['owes_me'])->sum('amount') + $userBalance['total_owed'], 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-red-600 mb-1">😬 You Owe</p>
                <p class="text-xl font-black text-red-600">${{ number_format(collect($settlement['i_owe'])->sum('amount'), 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-green-600 mb-1">🤑 They Owe</p>
                <p class="text-xl font-black text-green-600">${{ number_format(collect($settlement['owes_me'])->sum('amount'), 2) }}</p>
            </div>
            <div class="bg-white rounded-xl p-3 shadow-sm">
                <p class="text-xs font-bold text-blue-600 mb-1">📝 Expenses</p>
                <p class="text-xl font-black text-blue-600">{{ $expenses->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Settlement Breakdown -->
    @if($settlement['i_owe'] || $settlement['owes_me'])
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- You Owe -->
            @if(count($settlement['i_owe']) > 0)
                <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl shadow-lg p-6 flex flex-col">
                    <h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">😬</span>
                        <span>Pay These Friends!</span>
                    </h3>
                    <div class="space-y-3 overflow-y-auto max-h-96 flex-1 pr-2">
                        @foreach($settlement['i_owe'] as $debt)
                            <div class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $debt['to_user']->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $debt['expense']->title }}</p>
                                    </div>
                                    <p class="font-bold text-orange-600 flex-shrink-0">${{ number_format($debt['amount'], 2) }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        @if($debt['status'] === 'pending')
                                            <span class="inline-block px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded">Pending</span>
                                        @elseif($debt['status'] === 'paid')
                                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Paid</span>
                                        @else
                                            <span class="inline-block px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded">{{ ucfirst($debt['status']) }}</span>
                                        @endif
                                    </div>
                                    @if($debt['expense']->payer_id === auth()->id())
                                        <div class="flex gap-1">
                                            <a href="{{ route('groups.expenses.edit', ['group' => $group, 'expense' => $debt['expense']]) }}" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded hover:bg-blue-200">
                                                Edit
                                            </a>
                                            <form action="{{ route('groups.expenses.destroy', ['group' => $group, 'expense' => $debt['expense']]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded hover:bg-red-200">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Others Owe You -->
            @if(count($settlement['owes_me']) > 0)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl shadow-lg p-6 flex flex-col">
                    <h3 class="text-xl font-black text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">🤑</span>
                        <span>Friends Owe You!</span>
                    </h3>
                    <div class="space-y-3 overflow-y-auto max-h-96 flex-1 pr-2">
                        @foreach($settlement['owes_me'] as $credit)
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $credit['from_user']->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $credit['expense']->title }}</p>
                                    </div>
                                    <p class="font-bold text-green-600 flex-shrink-0">${{ number_format($credit['amount'], 2) }}</p>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        @if($credit['status'] === 'pending')
                                            <span class="inline-block px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded">Pending</span>
                                        @elseif($credit['status'] === 'paid')
                                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Paid</span>
                                        @else
                                            <span class="inline-block px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded">{{ ucfirst($credit['status']) }}</span>
                                        @endif
                                    </div>
                                    @if($credit['expense']->payer_id === auth()->id())
                                        <div class="flex gap-1">
                                            <a href="{{ route('groups.expenses.edit', ['group' => $group, 'expense' => $credit['expense']]) }}" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded hover:bg-blue-200">
                                                Edit
                                            </a>
                                            <form action="{{ route('groups.expenses.destroy', ['group' => $group, 'expense' => $credit['expense']]) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded hover:bg-red-200">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="bg-gradient-to-br from-green-100 via-emerald-100 to-teal-100 rounded-3xl shadow-xl p-8">
            <div class="text-center">
                <div class="text-7xl mb-4">🎉</div>
                <h3 class="text-3xl font-black text-green-900 mb-2">All Settled!</h3>
                <p class="text-lg font-semibold text-green-700">Everyone's square! No one owes anyone 🙌</p>
                <p class="text-sm text-green-600 mt-2">Time to add more expenses! 😄</p>
            </div>
        </div>
    @endif

    <!-- Squad Members -->
    <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">👥</span>
            <span>Squad Members</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($balances as $balance)
                <div class="bg-white rounded-xl p-5 shadow-md border-2 {{ $balance['net_balance'] >= 0 ? 'border-green-300' : 'border-red-300' }} hover:shadow-xl transition-all transform hover:scale-105">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center flex-shrink-0">
                            <span class="text-xl font-black text-white">{{ strtoupper(substr($balance['user']->name, 0, 1)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-lg font-bold text-gray-900 truncate">{{ $balance['user']->name }}</p>
                            @if($balance['user']->id === auth()->id())
                                <span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">That's You! 👋</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600">💸 Paid</span>
                            <span class="text-sm font-bold text-gray-900">${{ number_format($balance['total_paid'], 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-600">💰 Share</span>
                            <span class="text-sm font-bold text-gray-900">${{ number_format($balance['total_owed'], 2) }}</span>
                        </div>
                        <div class="pt-2 border-t-2 border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-bold {{ $balance['net_balance'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $balance['net_balance'] >= 0 ? '🤑 Gets Back' : '😬 Owes' }}
                                </span>
                                <span class="text-xl font-black {{ $balance['net_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $balance['net_balance'] >= 0 ? '+' : '' }}${{ number_format(abs($balance['net_balance']), 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6 p-4 bg-white rounded-xl border-2 border-indigo-200 text-center">
            <p class="text-sm font-semibold text-gray-700">
                <span class="text-lg">💡</span> 
                <span class="text-green-600">Green</span> = Gets money back • 
                <span class="text-red-600">Red</span> = Needs to pay
            </p>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="bg-gradient-to-br from-orange-50 via-amber-50 to-yellow-50 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-2">
            <span class="text-3xl">📜</span>
            <span>Recent Activity</span>
        </h2>
        @if(count($expenses) > 0)
            <div class="space-y-3">
                @foreach($expenses as $expense)
                    <div class="bg-white p-5 rounded-xl border-2 border-orange-200 hover:shadow-lg hover:border-orange-400 transition-all transform hover:scale-102">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-black text-lg text-gray-900 truncate flex items-center gap-2">
                                    <span>💰</span>
                                    {{ $expense->title }}
                                </h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-sm font-semibold text-gray-700">
                                        👤 {{ $expense->payer->name }} paid
                                    </span>
                                    <span class="text-gray-400">•</span>
                                    <span class="text-xs font-semibold text-gray-500">
                                        👥 {{ $expense->splits->count() }} people
                                    </span>
                                </div>
                                <p class="text-xs font-semibold text-gray-500 mt-1">
                                    📅 {{ $expense->date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <p class="text-2xl font-black text-orange-600">${{ number_format($expense->amount, 2) }}</p>
                                <span class="inline-block mt-1 px-3 py-1 bg-gradient-to-r from-blue-100 to-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                    {{ ucfirst(str_replace('_', ' ', $expense->split_type)) }} split
                                </span>
                            </div>
                        </div>
                        @if($expense->description)
                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-600 italic">💬 "{{ $expense->description }}"</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-xl p-8 text-center border-2 border-dashed border-orange-300">
                <div class="text-6xl mb-4">🤷</div>
                <p class="text-lg font-bold text-gray-700 mb-2">No expenses yet!</p>
                <p class="text-sm text-gray-600">Click "Add Expense" to get started 🚀</p>
            </div>
        @endif
    </div>
    </div>
</div>
@endsection
