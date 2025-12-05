<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Services\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
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

        // Get all group members for split selection
        $members = $group->members()->get();

        return view('expenses.create', compact('group', 'members'));
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
        ]);

        try {
            // Add group and payer info
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $group->members()->pluck('users.id')->toArray(),
                $validated['amount']
            );

            $expense = $this->expenseService->createExpense(
                $group,
                auth()->user(),
                $validated
            );

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
        $expense->load('payer', 'splits.user', 'comments.user', 'attachments');

        // Calculate settlement
        $settlement = $this->expenseService->getExpenseSettlement($expense);

        // Check if current user can edit/delete
        $canManage = $expense->payer_id === auth()->id() || $group->isAdmin(auth()->user());

        return view('expenses.show', compact('expense', 'group', 'settlement', 'canManage'));
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

        // Get group members for split selection
        $members = $group->members()->get();

        // Get current splits
        $currentSplits = $expense->splits()
            ->pluck('share_amount', 'user_id')
            ->toArray();

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
        ]);

        try {
            // Process splits
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $group->members()->pluck('users.id')->toArray(),
                $validated['amount']
            );

            $expense = $this->expenseService->updateExpense($expense, $validated);

            return redirect()
                ->route('groups.expenses.show', ['group' => $group, 'expense' => $expense])
                ->with('success', 'Expense updated successfully!');
        } catch (\Exception $e) {
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
            $expenseTitle = $expense->title;
            $this->expenseService->deleteExpense($expense);

            return redirect()
                ->route('groups.show', $group)
                ->with('success', "Expense '{$expenseTitle}' deleted successfully!");
        } catch (\Exception $e) {
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
    private function processSplits(string $splitType, ?array $splits, array $memberIds, float $totalAmount): array
    {
        if ($splitType === 'equal') {
            // Equal split among all members
            $splitAmount = round($totalAmount / count($memberIds), 2);
            $result = [];

            foreach ($memberIds as $memberId) {
                $result[$memberId] = $splitAmount;
            }

            // Handle rounding issues
            $totalSplit = array_sum($result);
            if ($totalSplit !== $totalAmount) {
                $diff = round($totalAmount - $totalSplit, 2);
                $result[$memberIds[0]] += $diff;
            }

            return $result;
        } else {
            // Custom split - validate that it matches total amount
            $customSplits = [];

            foreach ($memberIds as $memberId) {
                $customSplits[$memberId] = isset($splits[$memberId])
                    ? round((float) $splits[$memberId], 2)
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
