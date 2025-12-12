<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
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
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:png,jpeg,jpg,pdf|max:5120',
            'items_json' => 'nullable|json',
        ]);

        try {
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
                auth()->user(),
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

            // Process splits
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $group->members()->pluck('users.id')->toArray(),
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
                $result[$member->id] = $splitAmount;
            }

            // Handle rounding issues
            $totalSplit = array_sum($result);
            if (abs($totalSplit - $totalAmount) > 0.01) {
                $diff = round($totalAmount - $totalSplit, 2);
                $result[$members[0]->id] += $diff;
            }

            return $result;
        } else {
            // Custom split - validate that it matches total amount
            $customSplits = [];

            foreach ($members as $member) {
                $customSplits[$member->id] = isset($splits[$member->id])
                    ? round((float) $splits[$member->id], 2)
                    : 0;
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
}
