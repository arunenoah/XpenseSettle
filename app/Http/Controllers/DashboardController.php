<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display the user dashboard with summary and recent activity.
     */
    public function index()
    {
        $user = auth()->user();

        // Get user's groups with expense stats
        $groups = $user->groups()
            ->with('expenses')
            ->get()
            ->map(function ($group) use ($user) {
                return [
                    'group' => $group,
                    'total_expenses' => $group->expenses()->count(),
                    'user_is_admin' => $group->isAdmin($user),
                ];
            });

        // Get pending payments
        $pendingPayments = $this->paymentService->getPendingPaymentsForUser($user);

        // Get paid payments
        $paidPayments = \App\Models\Payment::whereHas('split', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('status', 'paid')
            ->with(['split.expense.payer', 'split.expense.group'])
            ->latest()
            ->limit(10)
            ->get();

        // Calculate totals across all groups
        $totalOwed = 0;
        $totalPaid = 0;
        $pendingCount = 0;

        foreach ($user->groups as $group) {
            $stats = $this->paymentService->getPaymentStats($user, $group->id);
            $totalOwed += $stats['pending_amount'];
            $totalPaid += $stats['paid_amount'];
            $pendingCount += $pendingPayments
                ->filter(function ($payment) use ($group) {
                    return $payment->split->expense->group_id === $group->id;
                })
                ->count();
        }

        // Get recent expenses across all groups
        $recentExpenses = Expense::whereHas('group.members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with('payer', 'group')
            ->latest()
            ->limit(5)
            ->get();

        // Get expenses where user is a participant
        $userExpenses = $user->expenseSplits()
            ->with('expense.group', 'expense.payer')
            ->latest()
            ->limit(5)
            ->get();

        // Get people who owe the user money (user is the payer, others haven't paid)
        $peopleOweMe = \App\Models\Payment::whereHas('split.expense', function ($q) use ($user) {
            $q->where('payer_id', $user->id);
        })
            ->whereHas('split', function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id);
            })
            ->where('status', 'pending')
            ->with(['split.user', 'split.expense.group'])
            ->get()
            ->groupBy('split.user_id')
            ->map(function ($payments) {
                $user = $payments->first()->split->user;
                $totalOwed = $payments->sum('split.share_amount');
                return [
                    'user' => $user,
                    'total_owed' => $totalOwed,
                    'payment_count' => $payments->count(),
                ];
            })
            ->sortByDesc('total_owed')
            ->take(5);

        return view('dashboard', [
            'user' => $user,
            'groups' => $groups,
            'pendingPayments' => $pendingPayments,
            'paidPayments' => $paidPayments,
            'peopleOweMe' => $peopleOweMe,
            'totalOwed' => $totalOwed,
            'totalPaid' => $totalPaid,
            'pendingCount' => $pendingCount,
            'recentExpenses' => $recentExpenses,
            'userExpenses' => $userExpenses,
        ]);
    }

    /**
     * Display group-specific dashboard for members.
     */
    public function groupDashboard(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        $user = auth()->user();

        // Get balances for all members
        $balances = app('App\Services\GroupService')->getGroupBalance($group);

        // Get user's specific balance
        $userBalance = $balances[$user->id] ?? [
            'user' => $user,
            'total_owed' => 0,
            'total_paid' => 0,
            'net_balance' => 0,
        ];

        // Get all expenses in this group
        $expenses = $group->expenses()
            ->with('payer', 'splits')
            ->latest()
            ->get();

        // Get pending payments for user in this group
        $pendingPayments = $user->expenseSplits()
            ->whereHas('expense', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->whereDoesntHave('payment', function ($q) {
                $q->where('status', 'paid');
            })
            ->with('expense', 'payment')
            ->get();

        // Get settlement summary (who owes whom)
        $settlement = $this->calculateSettlement($group, $user);

        return view('groups.dashboard', [
            'group' => $group,
            'balances' => $balances,
            'userBalance' => $userBalance,
            'expenses' => $expenses,
            'pendingPayments' => $pendingPayments,
            'settlement' => $settlement,
        ]);
    }

    /**
     * Calculate settlement for a user in a group.
     * Returns who owes the user money and who the user owes.
     */
    private function calculateSettlement(Group $group, $user)
    {
        $owesMe = [];    // Who owes the user
        $iOwe = [];      // Who the user owes

        foreach ($group->expenses as $expense) {
            // Handle itemwise expenses (no splits, full amount)
            if ($expense->split_type === 'itemwise') {
                if ($user->id !== $expense->payer_id) {
                    // User is not the payer - they owe the full amount to the payer
                    $iOwe[] = [
                        'to_user' => $expense->payer,
                        'expense' => $expense,
                        'amount' => $expense->amount,
                        'status' => 'pending', // Itemwise expenses are always pending (no payment tracking)
                    ];
                } else {
                    // User is the payer - all other members owe them
                    foreach ($group->members as $member) {
                        if ($member->id !== $user->id) {
                            $owesMe[] = [
                                'from_user' => $member,
                                'expense' => $expense,
                                'amount' => $expense->amount,
                                'status' => 'pending',
                            ];
                        }
                    }
                }
            } else {
                // Handle regular splits (equal, custom)
                foreach ($expense->splits as $split) {
                    if ($split->user_id === $user->id && $split->user_id !== $expense->payer_id) {
                        // User is a participant and is not the payer
                        $payment = $split->payment;

                        if (!$payment || $payment->status !== 'paid') {
                            $iOwe[] = [
                                'to_user' => $expense->payer,
                                'expense' => $expense,
                                'amount' => $split->share_amount,
                                'status' => $payment ? $payment->status : 'pending',
                            ];
                        }
                    } elseif ($expense->payer_id === $user->id && $split->user_id !== $user->id) {
                        // User is the payer, someone else is a participant
                        $payment = $split->payment;

                        if (!$payment || $payment->status !== 'paid') {
                            $owesMe[] = [
                                'from_user' => $split->user,
                                'expense' => $expense,
                                'amount' => $split->share_amount,
                                'status' => $payment ? $payment->status : 'pending',
                            ];
                        }
                    }
                }
            }
        }

        return [
            'owes_me' => $owesMe,
            'i_owe' => $iOwe,
        ];
    }
}
