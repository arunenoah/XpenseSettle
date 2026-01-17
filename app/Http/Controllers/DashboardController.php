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
     * API endpoint: Get user dashboard data
     * Returns JSON with balance and settlement details
     */
    public function apiIndex()
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

        // Calculate totals across all groups - grouped by currency
        $balancesByCurrency = [];  // currency => ['you_owe' => amount, 'they_owe' => amount]
        $settlementDetailsByCurrency = [];  // currency => detailed breakdown for modal
        $pendingCount = 0;

        // Use PaymentController for consistent calculations
        $paymentController = app(PaymentController::class);

        // Track person balances across all groups per currency
        $personBalancesByCurrency = [];  // currency => [personId => aggregated_data]

        foreach ($user->groups as $group) {
            $currency = $group->currency ?? 'INR';

            // Initialize currency if not exists
            if (!isset($balancesByCurrency[$currency])) {
                $balancesByCurrency[$currency] = [
                    'you_owe' => 0,
                    'they_owe' => 0,
                    'net' => 0
                ];
                $settlementDetailsByCurrency[$currency] = [
                    'you_owe_breakdown' => [],
                    'they_owe_breakdown' => [],
                ];
                $personBalancesByCurrency[$currency] = [];
            }

            // Get personal settlement for this group using PaymentController
            $settlement = $paymentController->calculateSettlement($group, $user);

            // Aggregate by person across groups in this currency
            foreach ($settlement as $item) {
                $personId = $item['user']->id;

                if (!isset($personBalancesByCurrency[$currency][$personId])) {
                    $personBalancesByCurrency[$currency][$personId] = [
                        'person' => $item['user'],
                        'net_amount' => 0,
                        'groups' => [],  // Track which groups have balances with this person
                        'all_expenses' => [],
                        'all_split_ids' => [],
                    ];
                }

                // Aggregate the net amount
                $personBalancesByCurrency[$currency][$personId]['net_amount'] += $item['net_amount'];

                // Track groups and expenses
                $personBalancesByCurrency[$currency][$personId]['groups'][] = [
                    'group_name' => $group->name,
                    'group_id' => $group->id,
                    'amount' => $item['net_amount'],
                    'expense_count' => count($item['expenses'] ?? []),
                    'expenses' => $item['expenses'] ?? [],
                    'split_ids' => $item['split_ids'] ?? [],
                ];

                // Aggregate all expenses and split IDs
                $personBalancesByCurrency[$currency][$personId]['all_expenses'] = array_merge(
                    $personBalancesByCurrency[$currency][$personId]['all_expenses'],
                    $item['expenses'] ?? []
                );
                $personBalancesByCurrency[$currency][$personId]['all_split_ids'] = array_merge(
                    $personBalancesByCurrency[$currency][$personId]['all_split_ids'],
                    $item['split_ids'] ?? []
                );
            }

            $pendingCount += $pendingPayments
                ->filter(function ($payment) use ($group) {
                    return $payment->split->expense->group_id === $group->id;
                })
                ->count();
        }

        // Now build the final breakdowns with NET balances per person
        foreach ($personBalancesByCurrency as $currency => $persons) {
            foreach ($persons as $personId => $data) {
                $netAmount = $data['net_amount'];

                if ($netAmount > 0) {
                    // User owes this person
                    $balancesByCurrency[$currency]['you_owe'] += $netAmount;

                    // Create separate breakdown items for each group to maintain backward compatibility with view
                    foreach ($data['groups'] as $groupData) {
                        $personData = [
                            'person' => $data['person'],
                            'amount' => $groupData['amount'],  // Per-group amount
                            'net_amount' => $groupData['amount'],
                            'group_name' => $groupData['group_name'],
                            'group_id' => $groupData['group_id'],
                            'expense_count' => $groupData['expense_count'],
                            'expenses' => $groupData['expenses'],
                            'split_ids' => $groupData['split_ids'],
                        ];
                        $settlementDetailsByCurrency[$currency]['you_owe_breakdown'][] = $personData;
                    }
                } elseif ($netAmount < 0) {
                    // This person owes user
                    $balancesByCurrency[$currency]['they_owe'] += abs($netAmount);

                    // Create separate breakdown items for each group to maintain backward compatibility with view
                    foreach ($data['groups'] as $groupData) {
                        $personData = [
                            'person' => $data['person'],
                            'amount' => abs($groupData['amount']),  // Per-group amount
                            'net_amount' => $groupData['amount'],
                            'group_name' => $groupData['group_name'],
                            'group_id' => $groupData['group_id'],
                            'expense_count' => $groupData['expense_count'],
                            'expenses' => $groupData['expenses'],
                            'split_ids' => $groupData['split_ids'],
                        ];
                        $settlementDetailsByCurrency[$currency]['they_owe_breakdown'][] = $personData;
                    }
                }
            }

            // Calculate net for this currency
            $balancesByCurrency[$currency]['net'] = $balancesByCurrency[$currency]['they_owe'] - $balancesByCurrency[$currency]['you_owe'];
        }

        // For backward compatibility, use primary currency (INR or first available)
        $primaryCurrency = 'INR';
        if (!isset($balancesByCurrency[$primaryCurrency]) && count($balancesByCurrency) > 0) {
            $primaryCurrency = array_key_first($balancesByCurrency);
        }
        
        $totalYouOwe = $balancesByCurrency[$primaryCurrency]['you_owe'] ?? 0;
        $totalTheyOweYou = $balancesByCurrency[$primaryCurrency]['they_owe'] ?? 0;
        $netBalance = $balancesByCurrency[$primaryCurrency]['net'] ?? 0;

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

        // Filter settlement details to remove near-zero amounts and organize by person
        $youOweBreakdown = $settlementDetailsByCurrency[$primaryCurrency]['you_owe_breakdown'] ?? [];
        $theyOweYouBreakdown = $settlementDetailsByCurrency[$primaryCurrency]['they_owe_breakdown'] ?? [];

        // Filter out near-zero amounts (< 0.01) and group by person
        $youOweFiltered = [];
        $youOweByPerson = []; // To aggregate across groups

        foreach ($youOweBreakdown as $item) {
            $amount = round($item['amount'], 2);
            if ($amount > 0.01) { // Only include meaningful amounts
                $personId = $item['person']['id'];

                if (!isset($youOweByPerson[$personId])) {
                    $youOweByPerson[$personId] = [
                        'person' => $item['person'],
                        'total_amount' => 0,
                        'groups' => []
                    ];
                }

                $youOweByPerson[$personId]['total_amount'] += $amount;
                $youOweByPerson[$personId]['groups'][] = [
                    'group_name' => $item['group_name'],
                    'group_id' => $item['group_id'],
                    'amount' => $amount,
                    'expense_count' => $item['expense_count'],
                    'expenses' => $item['expenses'] ?? [],
                ];
            }
        }

        $theyOweYouFiltered = [];
        $theyOweYouByPerson = []; // To aggregate across groups

        foreach ($theyOweYouBreakdown as $item) {
            $amount = round($item['amount'], 2);
            if ($amount > 0.01) { // Only include meaningful amounts
                $personId = $item['person']['id'];

                if (!isset($theyOweYouByPerson[$personId])) {
                    $theyOweYouByPerson[$personId] = [
                        'person' => $item['person'],
                        'total_amount' => 0,
                        'groups' => []
                    ];
                }

                $theyOweYouByPerson[$personId]['total_amount'] += $amount;
                $theyOweYouByPerson[$personId]['groups'][] = [
                    'group_name' => $item['group_name'],
                    'group_id' => $item['group_id'],
                    'amount' => $amount,
                    'expense_count' => $item['expense_count'],
                    'expenses' => $item['expenses'] ?? [],
                ];
            }
        }

        return [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'summary' => [
                    'you_owe' => round($totalYouOwe, 2),
                    'they_owe_you' => round($totalTheyOweYou, 2),
                    'net_balance' => round($netBalance, 2),
                    'pending_count' => $pendingCount,
                    'currency' => $primaryCurrency,
                ],
                'you_owe_people' => array_map(function ($item) {
                    return [
                        'person' => [
                            'id' => $item['person']['id'],
                            'name' => $item['person']['name'],
                            'email' => $item['person']['email'],
                        ],
                        'total_amount' => $item['total_amount'],
                        'groups' => $item['groups'],
                    ];
                }, $youOweByPerson),
                'they_owe_you_people' => array_map(function ($item) {
                    return [
                        'person' => [
                            'id' => $item['person']['id'],
                            'name' => $item['person']['name'],
                            'email' => $item['person']['email'],
                        ],
                        'total_amount' => $item['total_amount'],
                        'groups' => $item['groups'],
                    ];
                }, $theyOweYouByPerson),
            ]
        ];
    }

    /**
     * Display the user dashboard with summary and recent activity.
     * Web view - renders HTML dashboard
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

        // Calculate totals across all groups - grouped by currency
        $balancesByCurrency = [];  // currency => ['you_owe' => amount, 'they_owe' => amount]
        $settlementDetailsByCurrency = [];  // currency => detailed breakdown for modal
        $pendingCount = 0;

        // Use PaymentController for consistent calculations
        $paymentController = app(PaymentController::class);

        // Track person balances across all groups per currency
        $personBalancesByCurrency = [];  // currency => [personId => aggregated_data]

        foreach ($user->groups as $group) {
            $currency = $group->currency ?? 'INR';

            // Initialize currency if not exists
            if (!isset($balancesByCurrency[$currency])) {
                $balancesByCurrency[$currency] = [
                    'you_owe' => 0,
                    'they_owe' => 0,
                    'net' => 0
                ];
                $settlementDetailsByCurrency[$currency] = [
                    'you_owe_breakdown' => [],
                    'they_owe_breakdown' => [],
                ];
                $personBalancesByCurrency[$currency] = [];
            }

            // Get personal settlement for this group using PaymentController
            $settlement = $paymentController->calculateSettlement($group, $user);

            // Aggregate by person across groups in this currency
            foreach ($settlement as $item) {
                $personId = $item['user']->id;

                if (!isset($personBalancesByCurrency[$currency][$personId])) {
                    $personBalancesByCurrency[$currency][$personId] = [
                        'person' => $item['user'],
                        'net_amount' => 0,
                        'groups' => [],  // Track which groups have balances with this person
                        'all_expenses' => [],
                        'all_split_ids' => [],
                    ];
                }

                // Aggregate the net amount
                $personBalancesByCurrency[$currency][$personId]['net_amount'] += $item['net_amount'];

                // Track groups and expenses
                $personBalancesByCurrency[$currency][$personId]['groups'][] = [
                    'group_name' => $group->name,
                    'group_id' => $group->id,
                    'amount' => $item['net_amount'],
                    'expense_count' => count($item['expenses'] ?? []),
                    'expenses' => $item['expenses'] ?? [],
                    'split_ids' => $item['split_ids'] ?? [],
                ];

                // Aggregate all expenses and split IDs
                $personBalancesByCurrency[$currency][$personId]['all_expenses'] = array_merge(
                    $personBalancesByCurrency[$currency][$personId]['all_expenses'],
                    $item['expenses'] ?? []
                );
                $personBalancesByCurrency[$currency][$personId]['all_split_ids'] = array_merge(
                    $personBalancesByCurrency[$currency][$personId]['all_split_ids'],
                    $item['split_ids'] ?? []
                );
            }

            $pendingCount += $pendingPayments
                ->filter(function ($payment) use ($group) {
                    return $payment->split->expense->group_id === $group->id;
                })
                ->count();
        }

        // Now build the final breakdowns with NET balances per person
        foreach ($personBalancesByCurrency as $currency => $persons) {
            foreach ($persons as $personId => $data) {
                $netAmount = $data['net_amount'];

                if ($netAmount > 0) {
                    // User owes this person
                    $balancesByCurrency[$currency]['you_owe'] += $netAmount;

                    // Create separate breakdown items for each group to maintain backward compatibility with view
                    foreach ($data['groups'] as $groupData) {
                        $personData = [
                            'person' => $data['person'],
                            'amount' => $groupData['amount'],  // Per-group amount
                            'net_amount' => $groupData['amount'],
                            'group_name' => $groupData['group_name'],
                            'group_id' => $groupData['group_id'],
                            'expense_count' => $groupData['expense_count'],
                            'expenses' => $groupData['expenses'],
                            'split_ids' => $groupData['split_ids'],
                        ];
                        $settlementDetailsByCurrency[$currency]['you_owe_breakdown'][] = $personData;
                    }
                } elseif ($netAmount < 0) {
                    // This person owes user
                    $balancesByCurrency[$currency]['they_owe'] += abs($netAmount);

                    // Create separate breakdown items for each group to maintain backward compatibility with view
                    foreach ($data['groups'] as $groupData) {
                        $personData = [
                            'person' => $data['person'],
                            'amount' => abs($groupData['amount']),  // Per-group amount
                            'net_amount' => $groupData['amount'],
                            'group_name' => $groupData['group_name'],
                            'group_id' => $groupData['group_id'],
                            'expense_count' => $groupData['expense_count'],
                            'expenses' => $groupData['expenses'],
                            'split_ids' => $groupData['split_ids'],
                        ];
                        $settlementDetailsByCurrency[$currency]['they_owe_breakdown'][] = $personData;
                    }
                }
            }

            // Calculate net for this currency
            $balancesByCurrency[$currency]['net'] = $balancesByCurrency[$currency]['they_owe'] - $balancesByCurrency[$currency]['you_owe'];
        }

        // For backward compatibility, use primary currency (INR or first available)
        $primaryCurrency = 'INR';
        if (!isset($balancesByCurrency[$primaryCurrency]) && count($balancesByCurrency) > 0) {
            $primaryCurrency = array_key_first($balancesByCurrency);
        }

        $totalYouOwe = $balancesByCurrency[$primaryCurrency]['you_owe'] ?? 0;
        $totalTheyOweYou = $balancesByCurrency[$primaryCurrency]['they_owe'] ?? 0;
        $netBalance = $balancesByCurrency[$primaryCurrency]['net'] ?? 0;

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
            'balancesByCurrency' => $balancesByCurrency,
            'primaryCurrency' => $primaryCurrency,
            'settlementDetailsByCurrency' => $settlementDetailsByCurrency,
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
     * API endpoint: Get group-specific dashboard data
     */
    public function apiGroupDashboard(Request $request)
    {
        $groupId = $request->query('group_id') ?? $request->input('group_id');

        if (!$groupId) {
            return [
                'success' => false,
                'message' => 'Missing required parameter: group_id',
                'status' => 400
            ];
        }

        $group = Group::find($groupId);

        if (!$group) {
            return [
                'success' => false,
                'message' => 'Group not found',
                'status' => 404
            ];
        }

        if (!$group->hasMember(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not a member of this group',
                'status' => 403
            ];
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

        // Get settlement summary (who owes whom)
        $paymentController = app(PaymentController::class);
        $settlement = $paymentController->calculateSettlement($group, $user);

        // Calculate advance amounts for each member
        $memberAdvances = $this->calculateMemberAdvances($group);

        // Get recent paid payments for this group
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

        // Get recent advances for this group
        $recentAdvances = \App\Models\Advance::where('group_id', $group->id)
            ->with(['senders', 'sentTo'])
            ->latest()
            ->limit(10)
            ->get();

        // Get recent received payments for this group
        $recentReceivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->with(['fromUser', 'toUser'])
            ->latest()
            ->limit(10)
            ->get();

        // Calculate family statistics
        $userTotalHeadcount = $group->members()->sum('family_count') ?: 0;
        $userCount = $group->members()->count();
        if ($userTotalHeadcount == 0) {
            $userTotalHeadcount = $userCount;
        }

        $contactTotalHeadcount = $group->contacts()->sum('family_count') ?: 0;
        $contactCount = $group->contacts()->count();
        if ($contactTotalHeadcount == 0) {
            $contactTotalHeadcount = $contactCount;
        }

        $totalFamilyCount = $userTotalHeadcount + $contactTotalHeadcount;
        $totalExpenses = $expenses->sum('amount');
        $perHeadCost = $totalFamilyCount > 0 ? $totalExpenses / $totalFamilyCount : 0;
        $memberCount = $userCount + $contactCount;
        $perMemberShare = $memberCount > 0 ? $totalExpenses / $memberCount : 0;

        return [
            'success' => true,
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'currency' => $group->currency ?? 'USD',
                    'icon' => $group->icon,
                ],
                'user_balance' => [
                    'user_id' => $userBalance['user']->id ?? $user->id,
                    'total_owed' => round($userBalance['total_owed'] ?? 0, 2),
                    'total_paid' => round($userBalance['total_paid'] ?? 0, 2),
                    'net_balance' => round($userBalance['net_balance'] ?? 0, 2),
                ],
                'settlement' => array_map(function ($item) {
                    return [
                        'user' => [
                            'id' => $item['user']->id,
                            'name' => $item['user']->name,
                            'email' => $item['user']->email,
                        ],
                        'net_amount' => round($item['net_amount'], 2),
                        'expenses' => $item['expenses'] ?? [],
                    ];
                }, is_array($settlement) ? $settlement : $settlement->toArray()),
                'pending_payments_count' => $pendingPayments->count(),
                'recent_payments' => $recentPayments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->split->share_amount,
                        'payer' => $payment->split->expense->payer->name,
                        'created_at' => $payment->created_at,
                    ];
                })->toArray(),
                'statistics' => [
                    'total_expenses' => round($totalExpenses, 2),
                    'total_family_count' => $totalFamilyCount,
                    'per_head_cost' => round($perHeadCost, 2),
                    'member_count' => $memberCount,
                    'per_member_share' => round($perMemberShare, 2),
                ],
                'members' => $group->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'family_count' => $member->family_count ?? 1,
                    ];
                })->toArray(),
            ]
        ];
    }

    /**
     * Display group-specific dashboard for members.
     * Web view - renders HTML group dashboard
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

        // Get recent received payments for this group (for recent activity)
        $recentReceivedPayments = \App\Models\ReceivedPayment::where('group_id', $group->id)
            ->with(['fromUser', 'toUser'])
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
            'recentReceivedPayments' => $recentReceivedPayments,
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

        // Eager load splits with payment relationship to check payment status
        $expenses = $group->expenses()->with(['splits.payment', 'payer'])->get();

        foreach ($expenses as $expense) {
            // Skip itemwise expenses - they don't create splits and don't affect settlement
            if ($expense->split_type === 'itemwise') {
                continue;
            }

            // Skip fully paid expenses - they're settled and don't affect balances
            if ($expense->status === 'fully_paid') {
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
