<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Export group expenses as CSV.
     */
    public function exportGroupCSV(Group $group)
    {
        // Check authorization
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You must be a member of the group to export data');
        }

        $expenses = $group->expenses()
            ->with('payer', 'splits.user', 'splits.payment')
            ->orderBy('date', 'desc')
            ->get();

        // Create CSV content
        $csv = [];
        $csv[] = ['Date', 'Title', 'Description', 'Amount', 'Payer', 'Split Type', 'Status', 'Participants', 'Paid Count'];

        foreach ($expenses as $expense) {
            $participants = $expense->splits->pluck('user.name')->join(', ');
            $paidCount = $expense->splits->filter(function ($split) {
                return $split->payment && $split->payment->status === 'paid';
            })->count();

            $csv[] = [
                $expense->date->format('Y-m-d'),
                $expense->title,
                $expense->description ?? '',
                number_format($expense->amount, 2),
                $expense->payer->name,
                ucfirst($expense->split_type),
                ucfirst($expense->status),
                $participants,
                "{$paidCount}/{$expense->splits->count()}",
            ];
        }

        // Generate CSV file
        $filename = "group_{$group->id}_expenses_" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($csv) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export group balance summary as CSV.
     */
    public function exportBalanceCSV(Group $group)
    {
        // Check authorization
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You must be a member of the group to export data');
        }

        $balances = $this->groupService->getGroupBalance($group);

        // Create CSV content
        $csv = [];
        $csv[] = ['Member', 'Total Owed', 'Total Paid', 'Net Balance', 'Status'];

        foreach ($balances as $balance) {
            $status = $balance['net_balance'] > 0 ? 'Owed' : ($balance['net_balance'] < 0 ? 'Owes' : 'Settled');

            $csv[] = [
                $balance['user']->name,
                number_format($balance['total_owed'], 2),
                number_format($balance['total_paid'], 2),
                number_format(abs($balance['net_balance']), 2),
                $status,
            ];
        }

        // Generate CSV file
        $filename = "group_{$group->id}_balances_" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($csv) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export user payment history as CSV.
     */
    public function exportUserPayments(Request $request)
    {
        $user = auth()->user();
        $groupId = $request->get('group_id');

        $query = $user->expenseSplits()
            ->with('expense.group', 'expense.payer', 'payment')
            ->whereHas('expense');

        if ($groupId) {
            $query->whereHas('expense', function ($q) use ($groupId) {
                $q->where('group_id', $groupId);
            });
        }

        $splits = $query->get();

        // Create CSV content
        $csv = [];
        $csv[] = ['Date', 'Group', 'Expense', 'Payer', 'Your Share', 'Status', 'Paid Date', 'Notes'];

        foreach ($splits as $split) {
            $payment = $split->payment;
            $status = $payment ? ucfirst($payment->status) : 'Pending';
            $paidDate = $payment && $payment->paid_date ? $payment->paid_date : '';
            $notes = $payment ? $payment->notes : '';

            $csv[] = [
                $split->expense->date->format('Y-m-d'),
                $split->expense->group->name,
                $split->expense->title,
                $split->expense->payer->name,
                number_format($split->share_amount, 2),
                $status,
                $paidDate,
                $notes,
            ];
        }

        // Generate CSV file
        $filename = "my_payments_" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($csv) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export group summary report (detailed).
     */
    public function exportGroupSummary(Group $group)
    {
        // Check authorization
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You must be a member of the group to export data');
        }

        $expenses = $group->expenses()
            ->with('payer', 'splits.user', 'splits.payment')
            ->orderBy('date', 'desc')
            ->get();

        $balances = $this->groupService->getGroupBalance($group);

        // Create detailed CSV content
        $csv = [];

        // Header section
        $csv[] = ['Group Summary Report'];
        $csv[] = ['Group Name:', $group->name];
        $csv[] = ['Description:', $group->description ?? 'N/A'];
        $csv[] = ['Currency:', $group->currency];
        $csv[] = ['Members:', $group->members()->count()];
        $csv[] = ['Total Expenses:', $expenses->count()];
        $csv[] = ['Total Amount:', number_format($expenses->sum('amount'), 2)];
        $csv[] = ['Generated:', now()->format('Y-m-d H:i:s')];
        $csv[] = [];

        // Balance summary
        $csv[] = ['Member Balances'];
        $csv[] = ['Member', 'Total Owed', 'Total Paid', 'Net Balance', 'Status'];

        foreach ($balances as $balance) {
            $status = $balance['net_balance'] > 0 ? 'Owed' : ($balance['net_balance'] < 0 ? 'Owes' : 'Settled');
            $csv[] = [
                $balance['user']->name,
                number_format($balance['total_owed'], 2),
                number_format($balance['total_paid'], 2),
                number_format(abs($balance['net_balance']), 2),
                $status,
            ];
        }

        $csv[] = [];

        // Expense details
        $csv[] = ['Expense Details'];
        $csv[] = ['Date', 'Title', 'Description', 'Amount', 'Payer', 'Split Type', 'Status'];

        foreach ($expenses as $expense) {
            $csv[] = [
                $expense->date->format('Y-m-d'),
                $expense->title,
                $expense->description ?? '',
                number_format($expense->amount, 2),
                $expense->payer->name,
                ucfirst($expense->split_type),
                ucfirst($expense->status),
            ];

            // Add split details
            $csv[] = ['', 'Participant', 'Share Amount', 'Payment Status', 'Paid Date'];
            foreach ($expense->splits as $split) {
                $payment = $split->payment;
                $status = $payment ? ucfirst($payment->status) : 'Pending';
                $paidDate = $payment && $payment->paid_date ? $payment->paid_date : '';

                $csv[] = [
                    '',
                    $split->user->name,
                    number_format($split->share_amount, 2),
                    $status,
                    $paidDate,
                ];
            }

            $csv[] = [];
        }

        // Generate CSV file
        $filename = "group_{$group->id}_summary_" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($csv) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
