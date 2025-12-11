<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display audit logs for a specific group (admin only)
     */
    public function groupAuditLogs(Group $group)
    {
        // Check authorization - only group admin can view audit logs
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'You are not authorized to view this group\'s audit logs');
        }

        $logs = $this->auditService->getGroupLogs($group->id, 50);
        $summary = $this->auditService->getGroupActivitySummary($group->id, 7);

        return view('admin.audit-logs.group', [
            'group' => $group,
            'logs' => $logs,
            'summary' => $summary,
        ]);
    }

    /**
     * Get audit logs filtered by action
     */
    public function filterByAction(Group $group, Request $request)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Unauthorized');
        }

        $action = $request->get('action');
        $logs = \App\Models\AuditLog::forGroup($group->id)
            ->when($action, fn($q) => $q->byAction($action))
            ->with('user')
            ->latest()
            ->paginate(50);

        return view('admin.audit-logs.group', [
            'group' => $group,
            'logs' => $logs,
            'summary' => $this->auditService->getGroupActivitySummary($group->id, 7),
            'selectedAction' => $action,
        ]);
    }

    /**
     * Export audit logs as CSV
     */
    public function exportCsv(Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403, 'Unauthorized');
        }

        $logs = \App\Models\AuditLog::forGroup($group->id)
            ->with('user')
            ->latest()
            ->get();

        $filename = "audit_logs_{$group->name}_{now()->format('Y-m-d')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date/Time',
                'User',
                'Action',
                'Entity Type',
                'Description',
                'IP Address',
                'Status',
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->action,
                    $log->entity_type,
                    $log->description,
                    $log->ip_address,
                    $log->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
