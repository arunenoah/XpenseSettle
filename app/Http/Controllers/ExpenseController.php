<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\AuditService;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use App\Services\PlanService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private ExpenseService $expenseService;
    private NotificationService $notificationService;
    private PlanService $planService;
    private AuditService $auditService;

    public function __construct(ExpenseService $expenseService, NotificationService $notificationService, PlanService $planService, AuditService $auditService)
    {
        $this->expenseService = $expenseService;
        $this->notificationService = $notificationService;
        $this->planService = $planService;
        $this->auditService = $auditService;
    }

    /**
     * API endpoint: Get list of all expenses in a group
     */
    public function apiIndex(Request $request)
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

        $expenses = $group->expenses()
            ->with('payer', 'splits.user', 'splits.contact', 'splits.payment')
            ->latest()
            ->get();

        return [
            'success' => true,
            'data' => [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'currency' => $group->currency ?? 'USD',
                'expenses' => $expenses->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'title' => $expense->title,
                        'description' => $expense->description,
                        'amount' => round($expense->amount, 2),
                        'date' => $expense->date,
                        'category' => $expense->category,
                        'created_at' => $expense->created_at,
                        'payer' => [
                            'id' => $expense->payer->id,
                            'name' => $expense->payer->name,
                            'email' => $expense->payer->email,
                        ],
                        'split_count' => $expense->splits->count(),
                        'splits' => $expense->splits->map(function ($split) {
                            $participant = $split->user ?? $split->contact;
                            return [
                                'id' => $split->id,
                                'participant_id' => $participant->id,
                                'participant_name' => $participant->name,
                                'participant_email' => $participant->email ?? null,
                                'share_amount' => round($split->share_amount, 2),
                                'payment_status' => $split->payment ? $split->payment->status : 'pending',
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
                'total_expenses' => round($expenses->sum('amount'), 2),
                'expense_count' => $expenses->count(),
            ]
        ];
    }

    /**
     * API endpoint: Create a new expense with equal or custom split
     */
    public function apiStore(Request $request)
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string|in:Accommodation,Food & Dining,Groceries,Transport,Activities,Shopping,Utilities & Services,Fees & Charges,Other',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'payer_id' => 'nullable|exists:users,id',
        ]);

        try {
            // Get the selected payer (default to logged-in user)
            $payerId = $validated['payer_id'] ?? auth()->id();
            $payer = User::findOrFail($payerId);

            // Verify payer is a member of the group
            if (!$group->hasMember($payer)) {
                return [
                    'success' => false,
                    'message' => 'The selected payer is not a member of this group',
                    'status' => 400
                ];
            }

            // Get all members (users + contacts) for validation
            // For API, load fresh members to ensure relationships are loaded
            $group = $group->fresh();
            $allMembers = $group->allMembers()->get();

            if (count($allMembers) === 0) {
                // Fallback to members if allMembers returns empty
                $allMembers = $group->members()->get();
            }

            // Process splits based on type
            $processedSplits = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $allMembers,
                $validated['amount']
            );

            // Add processed splits to validated data
            $validated['splits'] = $processedSplits;

            $expense = $this->expenseService->createExpense(
                $group,
                $payer,
                $validated
            );

            // Log expense creation to audit trail
            $this->auditService->logSuccess(
                'create_expense',
                'Expense',
                "Expense '{$validated['title']}' ({$validated['amount']}) created in group '{$group->name}'",
                $expense->id,
                $group->id
            );

            // Log activity for timeline
            ActivityService::logExpenseCreated($group, $expense);

            // Send notification to group members about the new expense
            $this->notificationService->notifyExpenseCreated($expense, auth()->user());

            // Load relationships for response
            $expense->load('payer', 'splits.user', 'splits.contact', 'splits.payment');

            return [
                'success' => true,
                'data' => [
                    'id' => $expense->id,
                    'group_id' => $group->id,
                    'title' => $expense->title,
                    'description' => $expense->description,
                    'amount' => round($expense->amount, 2),
                    'date' => $expense->date,
                    'category' => $expense->category,
                    'split_type' => $expense->split_type,
                    'created_at' => $expense->created_at,
                    'payer' => [
                        'id' => $expense->payer->id,
                        'name' => $expense->payer->name,
                        'email' => $expense->payer->email,
                    ],
                    'split_count' => $expense->splits->count(),
                    'splits' => $expense->splits->map(function ($split) {
                        $participant = $split->user ?? $split->contact;
                        return [
                            'id' => $split->id,
                            'participant_id' => $participant->id,
                            'participant_name' => $participant->name,
                            'share_amount' => round($split->share_amount, 2),
                            'payment_status' => $split->payment ? $split->payment->status : 'pending',
                        ];
                    })->toArray(),
                ],
                'message' => 'Expense created successfully',
                'status' => 201,
            ];
        } catch (\Exception $e) {
            // Log failed expense creation
            $this->auditService->logFailed(
                'create_expense',
                'Expense',
                'Failed to create expense',
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * API endpoint: Update an existing expense
     */
    public function apiUpdate(Request $request)
    {
        $expenseId = $request->query('expense_id') ?? $request->input('expense_id');
        $groupId = $request->query('group_id') ?? $request->input('group_id');

        if (!$expenseId || !$groupId) {
            return [
                'success' => false,
                'message' => 'Missing required parameters: expense_id and group_id',
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

        $expense = Expense::find($expenseId);
        if (!$expense || $expense->group_id !== $group->id) {
            return [
                'success' => false,
                'message' => 'Expense not found in this group',
                'status' => 404
            ];
        }

        // Check authorization
        if ($expense->payer_id !== auth()->id() && !$group->isAdmin(auth()->user())) {
            return [
                'success' => false,
                'message' => 'You are not authorized to edit this expense',
                'status' => 403
            ];
        }

        // Check if expense is fully paid
        if ($expense->status === 'fully_paid') {
            return [
                'success' => false,
                'message' => 'Cannot edit a fully paid expense',
                'status' => 400
            ];
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string|in:Accommodation,Food & Dining,Groceries,Transport,Activities,Shopping,Utilities & Services,Fees & Charges,Other',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
        ]);

        try {
            // Track changes for audit log
            $changes = [];
            if ($expense->title !== $validated['title']) {
                $changes['title'] = ['from' => $expense->title, 'to' => $validated['title']];
            }
            if ($expense->amount != $validated['amount']) {
                $changes['amount'] = ['from' => $expense->amount, 'to' => $validated['amount']];
            }

            // Get all members (users + contacts) for validation
            // For API, load fresh members to ensure relationships are loaded
            $group = $group->fresh();
            $allMembers = $group->allMembers()->get();

            if (count($allMembers) === 0) {
                // Fallback to members if allMembers returns empty
                $allMembers = $group->members()->get();
            }

            // Process splits
            $processedSplits = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $allMembers,
                $validated['amount']
            );

            $validated['splits'] = $processedSplits;

            $expense = $this->expenseService->updateExpense($expense, $validated);

            // Log expense update if there were changes
            if (!empty($changes)) {
                $this->auditService->logSuccess(
                    'update_expense',
                    'Expense',
                    "Expense '{$validated['title']}' updated in group '{$group->name}'",
                    $expense->id,
                    $group->id,
                    $changes
                );
            }

            // Load relationships for response
            $expense->load('payer', 'splits.user', 'splits.contact', 'splits.payment');

            return [
                'success' => true,
                'data' => [
                    'id' => $expense->id,
                    'group_id' => $group->id,
                    'title' => $expense->title,
                    'description' => $expense->description,
                    'amount' => round($expense->amount, 2),
                    'date' => $expense->date,
                    'category' => $expense->category,
                    'split_type' => $expense->split_type,
                    'created_at' => $expense->created_at,
                    'updated_at' => $expense->updated_at,
                    'payer' => [
                        'id' => $expense->payer->id,
                        'name' => $expense->payer->name,
                        'email' => $expense->payer->email,
                    ],
                    'split_count' => $expense->splits->count(),
                    'splits' => $expense->splits->map(function ($split) {
                        $participant = $split->user ?? $split->contact;
                        return [
                            'id' => $split->id,
                            'participant_id' => $participant->id,
                            'participant_name' => $participant->name,
                            'share_amount' => round($split->share_amount, 2),
                            'payment_status' => $split->payment ? $split->payment->status : 'pending',
                        ];
                    })->toArray(),
                ],
                'message' => 'Expense updated successfully',
                'status' => 200,
            ];
        } catch (\Exception $e) {
            // Log failed expense update
            $this->auditService->logFailed(
                'update_expense',
                'Expense',
                'Failed to update expense',
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
                'status' => 400,
            ];
        }
    }

    /**
     * Show form for creating a new expense in a group.
     */
    public function create(Group $group)
    {
        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Get all group members (users + contacts) for split selection
        $members = $group->allMembers()->get();

        // Get plan information
        $canUseOCR = $this->planService->canUseOCR($group);
        $remainingOCRScans = $this->planService->getRemainingOCRScans($group);
        $planName = $this->planService->getPlanName($group);

        return view('expenses.create', compact('group', 'members', 'canUseOCR', 'remainingOCRScans', 'planName'));
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request, Group $group)
    {
        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string|in:Accommodation,Food & Dining,Groceries,Transport,Activities,Shopping,Utilities & Services,Fees & Charges,Other',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'payer_id' => 'required|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:png,jpeg,jpg,pdf|max:5120',
            'items_json' => 'nullable|json',
        ]);

        try {
            // Get the selected payer (default to logged-in user)
            $payerId = $request->input('payer_id', auth()->id());
            $payer = User::findOrFail($payerId);

            // Verify payer is a member of the group
            if (!$group->hasMember($payer)) {
                abort(403, 'The selected payer is not a member of this group');
            }

            // Get all members (users + contacts) for validation
            $allMembers = $group->allMembers()->get();

            // Add group and payer info
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $allMembers,
                $validated['amount']
            );

            $expense = $this->expenseService->createExpense(
                $group,
                $payer,
                $validated
            );

            // Log expense creation to audit trail
            $this->auditService->logSuccess(
                'create_expense',
                'Expense',
                "Expense '{$validated['title']}' ({$validated['amount']}) created in group '{$group->name}'",
                $expense->id,
                $group->id
            );

            // Log activity for timeline
            ActivityService::logExpenseCreated($group, $expense);

            // Send notification to group members about the new expense
            $this->notificationService->notifyExpenseCreated($expense, auth()->user());

            // Handle OCR extracted items if provided
            if (!empty($validated['items_json'])) {
                $this->expenseService->createExpenseItems($expense, $validated['items_json']);
            }

            // Handle attachments if uploaded
            if ($request->hasFile('attachments')) {
                $attachmentService = app('App\Services\AttachmentService');
                foreach ($request->file('attachments') as $file) {
                    try {
                        $attachmentService->uploadAttachment(
                            $file,
                            $expense,
                            'expenses'
                        );
                    } catch (\Exception $e) {
                        // Log attachment error but don't fail the whole operation
                        \Log::warning('Failed to upload attachment for expense ' . $expense->id . ': ' . $e->getMessage());
                    }
                }
            }

            return redirect()
                ->route('groups.expenses.show', ['group' => $group, 'expense' => $expense])
                ->with('success', 'Expense created successfully!');
        } catch (\Exception $e) {
            // Log failed expense creation
            $this->auditService->logFailed(
                'create_expense',
                'Expense',
                'Failed to create expense',
                $e->getMessage()
            );

            return back()
                ->withInput()
                ->with('error', 'Failed to create expense: ' . $e->getMessage());
        }
    }

    /**
     * Display expense details.
     */
    public function show(Group $group, Expense $expense)
    {
        // Verify expense belongs to the group
        if ($expense->group_id !== $group->id) {
            abort(404);
        }

        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Load relationships
        $expense->load('payer', 'splits.user', 'splits.contact', 'comments.user', 'attachments', 'items.assignedTo');

        // Calculate settlement
        $settlement = $this->expenseService->getExpenseSettlement($expense);

        return view('expenses.show', compact('expense', 'group', 'settlement'));
    }

    /**
     * Return expense details as HTML for modal popup (AJAX)
     */
    public function showModal(Group $group, Expense $expense)
    {
        // Verify expense belongs to the group
        if ($expense->group_id !== $group->id) {
            abort(404);
        }

        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Load relationships
        $expense->load('payer', 'splits.user', 'splits.contact', 'comments.user', 'attachments', 'items.assignedTo');

        // Calculate settlement
        $settlement = $this->expenseService->getExpenseSettlement($expense);

        // Render the modal content view
        $html = view('expenses.modal', compact('expense', 'group', 'settlement'))->render();

        return response()->json(['html' => $html]);
    }

    /**
     * Show form for editing an expense.
     */
    public function edit(Group $group, Expense $expense)
    {
        // Verify expense belongs to the group
        if ($expense->group_id !== $group->id) {
            abort(404);
        }

        // Check authorization - only payer or group admin can edit
        if ($expense->payer_id !== auth()->id() && !$group->isAdmin(auth()->user())) {
            abort(403, 'You are not authorized to edit this expense');
        }

        // Check if expense is fully paid
        if ($expense->status === 'fully_paid') {
            abort(403, 'Cannot edit a fully paid expense');
        }

        // Get group members for split selection (both users and contacts)
        $members = $group->allMembers()->get();

        // Get current splits - map by GroupMember ID
        $currentSplits = [];
        foreach ($expense->splits as $split) {
            // Find the corresponding GroupMember for this split
            $groupMember = $group->groupMembers()
                ->when($split->user_id, function ($q) use ($split) {
                    return $q->where('user_id', $split->user_id);
                })
                ->when($split->contact_id && !$split->user_id, function ($q) use ($split) {
                    return $q->where('contact_id', $split->contact_id);
                })
                ->first();

            if ($groupMember) {
                $currentSplits[$groupMember->id] = $split->share_amount;
            }
        }

        // Load attachments
        $expense->load('attachments');

        return view('expenses.edit', compact('expense', 'group', 'members', 'currentSplits'));
    }

    /**
     * Update the expense.
     */
    public function update(Request $request, Group $group, Expense $expense)
    {
        // Verify expense belongs to the group
        if ($expense->group_id !== $group->id) {
            abort(404);
        }

        // Check authorization
        if ($expense->payer_id !== auth()->id() && !$group->isAdmin(auth()->user())) {
            abort(403, 'You are not authorized to edit this expense');
        }

        // Check if expense is fully paid
        if ($expense->status === 'fully_paid') {
            abort(403, 'Cannot edit a fully paid expense');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string|in:Accommodation,Food & Dining,Groceries,Transport,Activities,Shopping,Utilities & Services,Fees & Charges,Other',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:png,jpeg,jpg,pdf|max:5120',
        ]);

        try {
            // Track changes for audit log
            $changes = [];
            if ($expense->title !== $validated['title']) {
                $changes['title'] = ['from' => $expense->title, 'to' => $validated['title']];
            }
            if ($expense->amount != $validated['amount']) {
                $changes['amount'] = ['from' => $expense->amount, 'to' => $validated['amount']];
            }
            if ($expense->category !== ($validated['category'] ?? 'Other')) {
                $changes['category'] = ['from' => $expense->category ?? 'Other', 'to' => $validated['category'] ?? 'Other'];
            }

            // Get all members (users + contacts) for validation
            $allMembers = $group->allMembers()->get();

            // Process splits
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $allMembers,
                $validated['amount']
            );

            $expense = $this->expenseService->updateExpense($expense, $validated);

            // Log expense update if there were changes
            if (!empty($changes)) {
                $this->auditService->logSuccess(
                    'update_expense',
                    'Expense',
                    "Expense '{$validated['title']}' updated in group '{$group->name}'",
                    $expense->id,
                    $group->id,
                    $changes
                );
            }

            // Handle attachments if uploaded
            if ($request->hasFile('attachments')) {
                $attachmentService = app('App\Services\AttachmentService');
                foreach ($request->file('attachments') as $file) {
                    try {
                        $attachmentService->uploadAttachment(
                            $file,
                            $expense,
                            'expenses'
                        );
                    } catch (\Exception $e) {
                        // Log attachment error but don't fail the whole operation
                        \Log::warning('Failed to upload attachment for expense ' . $expense->id . ': ' . $e->getMessage());
                    }
                }
            }

            return redirect()
                ->route('groups.expenses.show', ['group' => $group, 'expense' => $expense])
                ->with('success', 'Expense updated successfully!');
        } catch (\Exception $e) {
            // Log failed expense update
            $this->auditService->logFailed(
                'update_expense',
                'Expense',
                'Failed to update expense',
                $e->getMessage()
            );

            return back()
                ->withInput()
                ->with('error', 'Failed to update expense: ' . $e->getMessage());
        }
    }

    /**
     * Delete the expense.
     */
    public function destroy(Group $group, Expense $expense)
    {
        // Verify expense belongs to the group
        if ($expense->group_id !== $group->id) {
            abort(404);
        }

        // Check authorization - only payer or group admin can delete
        if ($expense->payer_id !== auth()->id() && !$group->isAdmin(auth()->user())) {
            abort(403, 'You are not authorized to delete this expense');
        }

        try {
            $expenseId = $expense->id;
            $expenseTitle = $expense->title;
            $this->expenseService->deleteExpense($expense);

            // Log expense deletion
            $this->auditService->logSuccess(
                'delete_expense',
                'Expense',
                "Expense '{$expenseTitle}' deleted from group '{$group->name}'",
                $expenseId,
                $group->id
            );

            return redirect()
                ->route('groups.show', $group)
                ->with('success', "Expense '{$expenseTitle}' deleted successfully!");
        } catch (\Exception $e) {
            // Log failed expense deletion
            $this->auditService->logFailed(
                'delete_expense',
                'Expense',
                'Failed to delete expense',
                $e->getMessage()
            );

            return back()->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Process splits based on split type.
     *
     * @param string $splitType
     * @param array|null $splits
     * @param array $memberIds
     * @param float $totalAmount
     * @return array
     */
    private function processSplits(string $splitType, ?array $splits, $members, float $totalAmount): array
    {
        if ($splitType === 'equal') {
            // Equal split among all members
            $memberCount = count($members);
            $splitAmount = round($totalAmount / $memberCount, 2);
            $result = [];

            foreach ($members as $member) {
                // For GroupMember objects, use user_id or contact_id as the key
                $memberId = $this->getMemberId($member);
                $result[$memberId] = $splitAmount;
            }

            // Handle rounding issues
            $totalSplit = array_sum($result);
            if (abs($totalSplit - $totalAmount) > 0.01) {
                $diff = round($totalAmount - $totalSplit, 2);
                $firstMemberId = $this->getMemberId($members[0]);
                $result[$firstMemberId] += $diff;
            }

            return $result;
        } else {
            // Custom split - validate that it matches total amount
            $customSplits = [];

            foreach ($members as $member) {
                // For GroupMember objects, use user_id or contact_id as the key
                $memberId = $this->getMemberId($member);

                // Try both integer and string keys for flexibility
                $key = $memberId;
                $stringKey = (string) $memberId;

                $value = 0;
                if (isset($splits[$key])) {
                    $value = round((float) $splits[$key], 2);
                } elseif (isset($splits[$stringKey])) {
                    $value = round((float) $splits[$stringKey], 2);
                }

                $customSplits[$memberId] = $value;
            }

            // Validate total
            $splitTotal = array_sum($customSplits);

            if (abs($splitTotal - $totalAmount) > 0.01) {
                throw new \Exception(
                    "Splits total (\${$splitTotal}) does not match expense amount (\${$totalAmount})"
                );
            }

            return $customSplits;
        }
    }

    /**
     * Get the actual member ID (user_id or contact_id) from a member object.
     * Handles both GroupMember objects and direct User/Contact objects.
     *
     * @param mixed $member
     * @return int
     */
    private function getMemberId($member): int
    {
        // If it's a GroupMember object
        if (method_exists($member, 'isActiveUser')) {
            return $member->isActiveUser() ? $member->user_id : $member->contact_id;
        }

        // If it's a direct User or Contact object
        return $member->id;
    }
}
