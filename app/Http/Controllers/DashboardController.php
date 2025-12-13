<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Services\ActivityService;
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
        $totalYouOwe = 0;      // Total amount user owes to others
        $totalTheyOweYou = 0;  // Total amount others owe to user
        $pendingCount = 0;

        foreach ($user->groups as $group) {
            // Get personal settlement for this group
            $settlement = $this->calculateSettlementForDashboard($group, $user);

            // Calculate totals from settlement
            foreach ($settlement as $item) {
                if ($item['net_amount'] > 0) {
                    // User owes this person
                    $totalYouOwe += $item['net_amount'];
                } else if ($item['net_amount'] < 0) {
                    // This person owes user
                    $totalTheyOweYou += abs($item['net_amount']);
                }
            }

            $pendingCount += $pendingPayments
                ->filter(function ($payment) use ($group) {
                    return $payment->split->expense->group_id === $group->id;
                })
                ->count();
        }

        // Calculate net balance
        $netBalance = $totalTheyOweYou - $totalYouOwe;

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
            'totalYouOwe' => round($totalYouOwe, 2),
            'totalTheyOweYou' => round($totalTheyOweYou, 2),
            'netBalance' => round($netBalance, 2),
            'pendingCount' => $pendingCount,
            'recentExpenses' => $recentExpenses,
            'userExpenses' => $userExpenses,
        ]);
    }

    /**
     * Calculate settlement for dashboard - simplified wrapper around calculateSettlement.
     * Returns array of settlement items with net_amount.
     */
    private function calculateSettlementForDashboard(Group $group, $user)
    {
        // Load all necessary relationships for settlement calculation
        $group->load([
            'expenses' => function ($q) {
                $q->latest();
            },
            'expenses.splits.user',
            'expenses.splits.contact',
            'expenses.splits.payment',
            'expenses.payer',
            'members',
        ]);

        return $this->calculateSettlement($group, $user);
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

        // Get all expenses in this group (with payment relationships for settlement calculation)
        $expenses = $group->expenses()
            ->with('payer', 'splits.payment', 'splits.user', 'splits.contact')
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

        // Get settlement summary (who owes whom) - use PaymentController's method for consistency
        $paymentController = app(PaymentController::class);
        $settlement = $paymentController->calculateSettlement($group, $user);

        // Calculate advance amounts for each member
        $memberAdvances = $this->calculateMemberAdvances($group);

        // Get recent paid payments for this group (for recent activity)
        $recentPayments = \App\Models\Payment::whereHas('split.expense', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })
            ->where('status', 'paid')
            ->with([
                'split.user',
                'split.expense.payer',
                'split.expense.group',
                'paidBy',
                'attachments'
            ])
            ->latest()
            ->limit(10)
            ->get();

        // Get recent advances for this group (for recent activity)
        $recentAdvances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->latest()
            ->limit(10)
            ->get();

        // Calculate family statistics
        // family_count is the total headcount for that member/contact (not additional)
        // Get total headcount from users and contacts
        $userTotalHeadcount = $group->members()->sum('family_count') ?: 0;
        // If no family_count set, default to 1 per user
        $userCount = $group->members()->count();
        if ($userTotalHeadcount == 0) {
            $userTotalHeadcount = $userCount;
        }

        // Add contact headcounts
        $contactTotalHeadcount = $group->contacts()->sum('family_count') ?: 0;
        $contactCount = $group->contacts()->count();
        // If no family_count set, default to 1 per contact
        if ($contactTotalHeadcount == 0) {
            $contactTotalHeadcount = $contactCount;
        }

        $totalFamilyCount = $userTotalHeadcount + $contactTotalHeadcount;
        $totalExpenses = $expenses->sum('amount');
        $totalFamilyCost = $totalExpenses;
        $perHeadCost = $totalFamilyCount > 0 ? $totalExpenses / $totalFamilyCount : 0;
        $memberCount = $userCount + $contactCount;
        $perMemberShare = $memberCount > 0 ? $totalExpenses / $memberCount : 0;

        return view('groups.dashboard', [
            'group' => $group,
            'balances' => $balances,
            'userBalance' => $userBalance,
            'expenses' => $expenses,
            'pendingPayments' => $pendingPayments,
            'settlement' => $settlement,
            'memberAdvances' => $memberAdvances,
            'recentPayments' => $recentPayments,
            'recentAdvances' => $recentAdvances,
            'totalFamilyCount' => $totalFamilyCount,
            'totalFamilyCost' => $totalFamilyCost,
            'perHeadCost' => $perHeadCost,
            'memberCount' => $memberCount,
            'perMemberShare' => $perMemberShare,
        ]);
    }

    /**
     * Calculate settlement for a user in a group with split details.
     * Returns net balance with each person plus associated split IDs for marking as paid.
     */
    private function calculateSettlementWithPayments(Group $group, $user, $expenses = null)
    {
        $settlement = $this->calculateSettlement($group, $user);

        // Use passed expenses to avoid lazy loading
        if ($expenses === null) {
            $expenses = $group->expenses()->with('payer', 'splits.payment', 'splits.user', 'splits.contact')->get();
        }

        // Enrich settlement with split IDs
        $enrichedSettlement = [];
        foreach ($settlement as $item) {
            $splitIds = [];
            $otherUserId = $item['user']->id;

            // Find all relevant splits for this settlement
            foreach ($expenses as $expense) {
                if ($item['net_amount'] > 0) {
                    // User owes this person (item['user']) - they are the payer
                    if ($expense->payer_id === $otherUserId) {
                        foreach ($expense->splits as $split) {
                            // Only include user splits, skip contacts
                            if ($split->user_id === $user->id && !$split->contact_id) {
                                // Include split ID for marking as paid
                                $splitIds[] = $split->id;
                            }
                        }
                    }
                }
            }

            $item['split_ids'] = $splitIds;
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
            // Skip itemwise expenses - they don't create splits and don't affect settlement
            if ($expense->split_type === 'itemwise') {
                continue;
            }

            // Skip expenses where user is both payer and sole participant (self-payment)
            $firstSplit = $expense->splits->first();
            if ($expense->payer_id === $user->id && $expense->splits->count() === 1 && $firstSplit && $firstSplit->user_id === $user->id) {
                continue;
            }

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
                } elseif ($expense->payer_id === $user->id && $split->user_id && $split->user_id !== $user->id) {
                    // User is the payer, a user is a participant (owes user money)
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
                } elseif ($expense->payer_id === $user->id && $split->contact_id && !$split->user_id) {
                    // User is the payer, a contact is a participant (owes user money)
                    // Contacts always owe the payer, so we track this as negative (they owe user)
                    $contactId = $split->contact_id;

                    // Use contact_id as key with "contact_" prefix to avoid collision with user IDs
                    $balanceKey = "contact_{$contactId}";

                    if (!isset($netBalances[$balanceKey])) {
                        $netBalances[$balanceKey] = [
                            'user' => $split->contact,
                            'is_contact' => true,
                            'net_amount' => 0,
                            'status' => 'pending',
                        ];
                    }
                    // Negative amount means contact owes the user
                    $netBalances[$balanceKey]['net_amount'] -= $split->share_amount;
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

                // Skip self-advances (user sending to themselves)
                if ($recipientId !== $user->id) {
                    $advanceAmount = $advance->amount_per_person;

                    if (!isset($netBalances[$recipientId])) {
                        $netBalances[$recipientId] = [
                            'user' => $advance->sentTo,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                        ];
                    }

                    // Advance payment reduces what recipient owes
                    // If net_amount is positive (user owes recipient): subtract to reduce debt
                    // If net_amount is negative (recipient owes user): add to reduce their debt
                    if ($netBalances[$recipientId]['net_amount'] >= 0) {
                        $netBalances[$recipientId]['net_amount'] -= $advanceAmount;
                    } else {
                        // Negative amount: adding makes it less negative (reduces what recipient owes)
                        $netBalances[$recipientId]['net_amount'] += $advanceAmount;
                    }
                }
            }

            // Check if user received an advance from someone
            if ($advance->sent_to_user_id === $user->id) {
                // Someone sent this advance to the user
                foreach ($advance->senders as $sender) {
                    // Skip if sender is the user themselves
                    if ($sender->id === $user->id) {
                        continue;
                    }

                    $senderId = $sender->id;
                    $advanceAmount = $advance->amount_per_person;

                    if (!isset($netBalances[$senderId])) {
                        $netBalances[$senderId] = [
                            'user' => $sender,
                            'net_amount' => 0,
                            'status' => 'pending',
                            'expenses' => [],
                        ];
                    }

                    // Sender paid advance to user - reduces what user owes to sender
                    // If net_amount is positive (user owes sender): subtract to reduce debt
                    // If net_amount is negative (sender owes user): add to reduce what sender owes
                    if ($netBalances[$senderId]['net_amount'] >= 0) {
                        $netBalances[$senderId]['net_amount'] -= $advanceAmount;
                    } else {
                        // Negative amount: adding makes it less negative (reduces what sender owes)
                        $netBalances[$senderId]['net_amount'] += $advanceAmount;
                    }
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
                    'amount' => abs($data['net_amount']),  // Final amount after all calculations including advances
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

    /**
     * Display the ONE authoritative trip summary page.
     *
     * This is the single source of truth for settlement in a group.
     * Shows:
     * - Total spent across entire group
     * - Per-person summary (what each person paid)
     * - Final settlement (minimum transactions needed to settle)
     * - Breakdown of expenses
     */
    public function groupSummary(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        $user = auth()->user();

        // Get all expenses and advances in this group
        $expenses = $group->expenses()
            ->where('split_type', '!=', 'itemwise')  // Exclude itemwise from settlement
            ->with('payer', 'splits.user', 'splits.contact', 'splits.payment')
            ->get();

        $advances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->get();

        // Calculate total spent by each member
        $totalSpentByMember = [];
        $totalSharedByMember = [];  // What they should pay (their share)
        $totalAmount = 0;

        foreach ($group->members as $member) {
            $totalSpentByMember[$member->id] = 0;
            $totalSharedByMember[$member->id] = 0;
        }

        // Calculate from expenses
        foreach ($expenses as $expense) {
            $totalAmount += $expense->amount;
            $totalSpentByMember[$expense->payer_id] = ($totalSpentByMember[$expense->payer_id] ?? 0) + $expense->amount;

            foreach ($expense->splits as $split) {
                $totalSharedByMember[$split->user_id] = ($totalSharedByMember[$split->user_id] ?? 0) + $split->share_amount;
            }
        }

        // Add advances to total spent
        foreach ($advances as $advance) {
            $totalAmount += ($advance->amount_per_person * count($advance->senders));
            foreach ($advance->senders as $sender) {
                $totalSpentByMember[$sender->id] = ($totalSpentByMember[$sender->id] ?? 0) + $advance->amount_per_person;
            }
            // Advances reduce what the recipient needs to pay
            $totalSharedByMember[$advance->sent_to_user_id] = max(0, ($totalSharedByMember[$advance->sent_to_user_id] ?? 0) - ($advance->amount_per_person * count($advance->senders)));
        }

        // Calculate net balance for each member (what they paid - what they owe)
        $memberSummary = [];
        foreach ($group->members as $member) {
            $paid = $totalSpentByMember[$member->id] ?? 0;
            $owes = $totalSharedByMember[$member->id] ?? 0;
            $netBalance = $paid - $owes;

            $memberSummary[$member->id] = [
                'user' => $member,
                'paid' => $paid,
                'owes' => $owes,
                'balance' => $netBalance,  // Positive = they're owed, Negative = they owe
            ];
        }

        // Calculate minimal settlement transactions
        $settlement = $this->calculateMinimalSettlement($memberSummary);

        // Get payment history for transparency
        $paidPayments = \App\Models\Payment::whereHas('split.expense', function ($q) use ($group) {
            $q->where('group_id', $group->id);
        })
            ->where('status', 'paid')
            ->with([
                'split.user',
                'split.expense.payer',
                'paidBy',
                'attachments'
            ])
            ->latest()
            ->get();

        // Get activity timeline for the group
        $activities = ActivityService::getGroupActivities($group->id, 100);

        return view('groups.summary', [
            'group' => $group,
            'user' => $user,
            'totalAmount' => $totalAmount,
            'memberSummary' => collect($memberSummary)->sortByDesc('balance'),
            'settlement' => $settlement,
            'expenseCount' => $expenses->count(),
            'advanceCount' => $advances->count(),
            'paidPayments' => $paidPayments,
            'activities' => $activities,
        ]);
    }

    /**
     * Export group timeline as PDF/Printable document
     */
    public function exportTimelinePdf(Group $group)
    {
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Get activity timeline for the group
        $activities = ActivityService::getTimelineForPdf($group->id);

        return view('groups.timeline-pdf', [
            'group' => $group,
            'activities' => $activities,
            'exportedAt' => now(),
        ]);
    }

    /**
     * Calculate the minimal settlement transactions needed to settle all debts.
     * Uses a greedy algorithm to minimize the number of transactions.
     */
    private function calculateMinimalSettlement($memberSummary)
    {
        // Extract creditors (positive balance - owed money) and debtors (negative balance - owe money)
        $creditors = [];  // ['user' => User, 'amount' => positive amount]
        $debtors = [];    // ['user' => User, 'amount' => positive amount]

        foreach ($memberSummary as $memberId => $summary) {
            if ($summary['balance'] > 0.01) {  // They're owed money
                $creditors[] = [
                    'user' => $summary['user'],
                    'amount' => round($summary['balance'], 2),
                ];
            } elseif ($summary['balance'] < -0.01) {  // They owe money
                $debtors[] = [
                    'user' => $summary['user'],
                    'amount' => round(abs($summary['balance']), 2),
                ];
            }
        }

        // Sort by amount (largest first)
        usort($creditors, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        usort($debtors, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        // Match creditors and debtors
        $transactions = [];
        foreach ($debtors as &$debtor) {
            while ($debtor['amount'] > 0.01 && !empty($creditors)) {
                $creditor = &$creditors[0];

                $amount = min($debtor['amount'], $creditor['amount']);
                $amount = round($amount, 2);

                $transactions[] = [
                    'from' => $debtor['user'],
                    'to' => $creditor['user'],
                    'amount' => $amount,
                ];

                $debtor['amount'] = round($debtor['amount'] - $amount, 2);
                $creditor['amount'] = round($creditor['amount'] - $amount, 2);

                if ($creditor['amount'] < 0.01) {
                    array_shift($creditors);
                }
            }
        }

        return $transactions;
    }
}
