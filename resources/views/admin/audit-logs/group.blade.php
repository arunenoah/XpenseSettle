@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
                    <p class="mt-2 text-gray-600">{{ $group->name }}</p>
                </div>
                <a href="{{ route('groups.show', $group) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    ← Back to Group
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Activity Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Total Actions (7 days)</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ $summary['total_actions'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Active Users</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $summary['by_user']->count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Action Types</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">{{ $summary['by_action']->count() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Failed Actions</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ $summary['failed_actions'] }}</p>
            </div>
        </div>

        <!-- Filters and Export -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Action</label>
                    <form method="GET" class="flex gap-2">
                        <select name="action" onchange="this.form.submit()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Actions</option>
                            <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                            <option value="logout" {{ request('action') === 'logout' ? 'selected' : '' }}>Logout</option>
                            <option value="create_group" {{ request('action') === 'create_group' ? 'selected' : '' }}>Create Group</option>
                            <option value="update_group" {{ request('action') === 'update_group' ? 'selected' : '' }}>Update Group</option>
                            <option value="add_member" {{ request('action') === 'add_member' ? 'selected' : '' }}>Add Member</option>
                            <option value="remove_member" {{ request('action') === 'remove_member' ? 'selected' : '' }}>Remove Member</option>
                            <option value="add_contact" {{ request('action') === 'add_contact' ? 'selected' : '' }}>Add Contact</option>
                            <option value="create_expense" {{ request('action') === 'create_expense' ? 'selected' : '' }}>Create Expense</option>
                            <option value="update_expense" {{ request('action') === 'update_expense' ? 'selected' : '' }}>Update Expense</option>
                            <option value="delete_expense" {{ request('action') === 'delete_expense' ? 'selected' : '' }}>Delete Expense</option>
                            <option value="mark_paid" {{ request('action') === 'mark_paid' ? 'selected' : '' }}>Mark Paid</option>
                            <option value="approve_payment" {{ request('action') === 'approve_payment' ? 'selected' : '' }}>Approve Payment</option>
                            <option value="reject_payment" {{ request('action') === 'reject_payment' ? 'selected' : '' }}>Reject Payment</option>
                        </select>
                    </form>
                </div>
                <a href="{{ route('admin.audit-logs.export', $group) }}" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    📥 Export CSV
                </a>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date/Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Entity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $log->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($log->user)
                                        <span class="font-semibold text-gray-900">{{ $log->user->name }}</span>
                                    @else
                                        <span class="text-gray-500">System</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                        {{ str_replace('_', ' ', ucwords($log->action)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $log->entity_type }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <span title="{{ $log->description }}">
                                        {{ Str::limit($log->description, 60) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono text-xs">
                                    {{ $log->ip_address ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($log->status === 'success')
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                            ✓ Success
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold" title="{{ $log->error_message }}">
                                            ✕ Failed
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No audit logs found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
