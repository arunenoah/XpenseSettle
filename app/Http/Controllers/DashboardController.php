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

        // Get pending payments (exclude deleted groups)
        $pendingPayments = $this->paymentService->getPendingPaymentsForUser($user)
            ->load(['split.expense.group' => function ($q) {
                $q->withoutTrashed();
            }])
            ->filter(function ($payment) {
                return $payment->split->expense->group !== null;
            })
            ->values();

        // Get paid payments
        $paidPayments = \App\Models\Payment::whereHas('split', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->where('status', 'paid')
            ->with(['split.expense.payer', 'split.expense.group' => function ($q) {
                $q->withoutTrashed();
            }])
            ->latest()
            ->limit(10)
            ->get()
            ->filter(function ($payment) {
                return $payment->split->expense->group !== null;
            });

        // Calculate totals across all groups
        $totalOwed = 0;
        $totalPaid = 0;
        $pendingCount = 0;

        foreach ($user->groups as $group) {
            // Get payment stats from splits
            $stats = $this->paymentService->getPaymentStats($user, $group->id);
            $totalOwed += $stats['pending_amount'];
            $totalPaid += $stats['paid_amount'];

            // Add itemwise expense amounts
            $itemwiseExpenses = \App\Models\Expense::where('group_id', $group->id)
                ->where('split_type', 'itemwise')
                ->get();

            foreach ($itemwiseExpenses as $expense) {
                if ($user->id !== $expense->payer_id) {
                    // User is not the payer - they owe this amount
                    $totalOwed += $expense->amount;
                } else {
                    // User is the payer - they should get this back
                    // Count as potentially paid if split-up among members
                    $totalPaid += $expense->amount;
                }
            }

            $pendingCount += $pendingPayments
                ->filter(function ($payment) use ($group) {
                    return $payment->split->expense->group_id === $group->id;
                })
                ->count();
        }

        // Get recent expenses across all groups (exclude deleted groups)
        $recentExpenses = Expense::whereHas('group', function ($q) {
            $q->withoutTrashed();
        })
            ->whereHas('group.members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['payer', 'group' => function ($q) {
                $q->withoutTrashed();
            }])
            ->latest()
            ->limit(5)
            ->get();

        // Get expenses where user is a participant (exclude deleted groups)
        $userExpenses = $user->expenseSplits()
            ->whereHas('expense.group', function ($q) {
                $q->withoutTrashed();
            })
            ->with(['expense.group' => function ($q) {
                $q->withoutTrashed();
            }, 'expense.payer'])
            ->latest()
            ->limit(5)
            ->get();

        // Get people who owe the user money (exclude deleted groups)
        $peopleOweMe = \App\Models\Payment::whereHas('split.expense', function ($q) use ($user) {
            $q->where('payer_id', $user->id);
        })
            ->whereHas('split.expense.group', function ($q) {
                $q->withoutTrashed();
            })
            ->whereHas('split', function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id);
            })
            ->where('status', 'pending')
            ->with(['split.user', 'split.expense.group' => function ($q) {
                $q->withoutTrashed();
            }])
            ->get()
            ->filter(function ($payment) {
                return $payment->split->expense->group !== null;
            })
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
        $settlement = $this->calculateSettlementWithPayments($group, $user);

        // Calculate advance amounts for each member
        $memberAdvances = $this->calculateMemberAdvances($group);

        return view('groups.dashboard', [
            'group' => $group,
            'balances' => $balances,
            'userBalance' => $userBalance,
            'expenses' => $expenses,
            'pendingPayments' => $pendingPayments,
            'settlement' => $settlement,
            'memberAdvances' => $memberAdvances,
        ]);
    }

    /**
     * Calculate settlement for a user in a group with payment details.
     * Returns net balance with each person plus associated payment IDs.
     */
    private function calculateSettlementWithPayments(Group $group, $user)
    {
        $settlement = $this->calculateSettlement($group, $user);

        // Enrich settlement with payment IDs
        $enrichedSettlement = [];
        foreach ($settlement as $item) {
            $paymentIds = [];
            $otherUserId = $item['user']->id;

            // Find all relevant payments for this settlement
            foreach ($group->expenses as $expense) {
                if ($item['net_amount'] > 0) {
                    // User owes this person (item['user']) - they are the payer
                    if ($expense->payer_id === $otherUserId) {
                        foreach ($expense->splits as $split) {
                            if ($split->user_id === $user->id) {
                                $payment = $split->payment;
                                if ($payment) {
                                    // Include payment ID if it's pending
                                    if ($payment->status === 'pending') {
                                        $paymentIds[] = $payment->id;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $item['payment_ids'] = $paymentIds;
            $enrichedSettlement[] = $item;
        }

        return $enrichedSettlement;
    }

    /**
     * Calculate settlement for a user in a group.
     * Returns net balance with each person (positive = user owes, negative = person owes user).
     */
    private function calculateSettlement(Group $group, $user)
    {
        // Maps to track amounts owed between user and each other person
        $netBalances = [];  // User ID => [user_obj, net_amount, status]

        foreach ($group->expenses as $expense) {
            // Handle itemwise expenses (no splits, full amount)
            if ($expense->split_type === 'itemwise') {
                if ($user->id !== $expense->payer_id) {
                    // User is not the payer - they owe the full amount to the payer
                    $payerId = $expense->payer_id;
                    if (!isset($netBalances[$payerId])) {
                        $netBalances[$payerId] = [
                            'user' => $expense->payer,
                            'net_amount' => 0,
                            'status' => 'pending',
                        ];
                    }
                    $netBalances[$payerId]['net_amount'] += $expense->amount;
                } else {
                    // User is the payer - they are owed by each member
                    foreach ($group->members as $member) {
                        if ($member->id !== $user->id) {
                            $memberId = $member->id;
                            if (!isset($netBalances[$memberId])) {
                                $netBalances[$memberId] = [
                                    'user' => $member,
                                    'net_amount' => 0,
                                    'status' => 'pending',
                                ];
                            }
                            $netBalances[$memberId]['net_amount'] -= $expense->amount;
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
                            $payerId = $expense->payer_id;
                            if (!isset($netBalances[$payerId])) {
                                $netBalances[$payerId] = [
                                    'user' => $expense->payer,
                                    'net_amount' => 0,
                                    'status' => 'pending',
                                ];
                            }
                            $netBalances[$payerId]['net_amount'] += $split->share_amount;
                        }
                    } elseif ($expense->payer_id === $user->id && $split->user_id !== $user->id) {
                        // User is the payer, someone else is a participant
                        $payment = $split->payment;

                        if (!$payment || $payment->status !== 'paid') {
                            $memberId = $split->user_id;
                            if (!isset($netBalances[$memberId])) {
                                $netBalances[$memberId] = [
                                    'user' => $split->user,
                                    'net_amount' => 0,
                                    'status' => 'pending',
                                ];
                            }
                            $netBalances[$memberId]['net_amount'] -= $split->share_amount;
                        }
                    }
                }
            }
        }

        // Account for advances
        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        foreach ($advances as $advance) {
            // Check if user is a sender of this advance (using loaded collection)
            if ($advance->senders->contains('id', $user->id)) {
                // User sent this advance to someone
                $recipientId = $advance->sent_to_user_id;
                $advanceAmount = $advance->amount_per_person;

                if (!isset($netBalances[$recipientId])) {
                    $netBalances[$recipientId] = [
                        'user' => $advance->sentTo,
                        'net_amount' => 0,
                        'status' => 'pending',
                    ];
                }

                // Advance reduces what the user owes (positive amount = owes)
                $netBalances[$recipientId]['net_amount'] -= $advanceAmount;
            }

            // Check if user received an advance from someone
            if ($advance->sent_to_user_id === $user->id) {
                // Someone sent this advance to the user
                foreach ($advance->senders as $sender) {
                    $senderId = $sender->id;
                    $advanceAmount = $advance->amount_per_person;

                    if (!isset($netBalances[$senderId])) {
                        $netBalances[$senderId] = [
                            'user' => $sender,
                            'net_amount' => 0,
                            'status' => 'pending',
                        ];
                    }

                    // Advance reduces what they owe to user (negative amount = owed)
                    $netBalances[$senderId]['net_amount'] += $advanceAmount;
                }
            }
        }

        // Convert to settlement array, filtering out zero balances
        // Positive net_amount = user owes this person
        // Negative net_amount = this person owes user (we show as positive amount they owe)
        $settlements = [];
        foreach ($netBalances as $personId => $data) {
            if ($data['net_amount'] != 0) {
                $settlements[] = [
                    'user' => $data['user'],
                    'amount' => abs($data['net_amount']),
                    'net_amount' => $data['net_amount'],  // Positive = user owes, Negative = user is owed
                    'status' => $data['status'],
                ];
            }
        }

        return $settlements;
    }

    /**
     * Calculate total advances paid by each group member.
     * Returns array with user_id => total_advance_amount paid by that user.
     */
    private function calculateMemberAdvances(Group $group)
    {
        $memberAdvances = [];

        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with('senders')
            ->get();

        foreach ($advances as $advance) {
            foreach ($advance->senders as $sender) {
                if (!isset($memberAdvances[$sender->id])) {
                    $memberAdvances[$sender->id] = 0;
                }
                $memberAdvances[$sender->id] += $advance->amount_per_person;
            }
        }

        return $memberAdvances;
    }
}
