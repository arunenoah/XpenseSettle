<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Group;
use App\Services\ActivityService;
use App\Services\AuditService;
use App\Services\ExpenseService;
use App\Services\NotificationService;
use App\Services\OcrService;
use App\Services\PlanService;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AddExpenseOCRController: Handles the OCR-enhanced expense creation flow
 * integrating Google Cloud Vision for automatic receipt scanning and data extraction.
 *
 * This controller provides a separate flow from the standard ExpenseController to:
 * - Allow testing of OCR functionality without affecting existing feature
 * - Maintain backward compatibility with existing AddExpense flow
 * - Provide a dedicated UI/UX for OCR-based expense creation
 */
class AddExpenseOCRController extends Controller
{
    private ExpenseService $expenseService;
    private NotificationService $notificationService;
    private PlanService $planService;
    private AuditService $auditService;
    private OcrService $ocrService;
    private AttachmentService $attachmentService;

    /**
     * Constructor with dependency injection.
     */
    public function __construct(
        ExpenseService $expenseService,
        NotificationService $notificationService,
        PlanService $planService,
        AuditService $auditService,
        OcrService $ocrService,
        AttachmentService $attachmentService
    ) {
        $this->expenseService = $expenseService;
        $this->notificationService = $notificationService;
        $this->planService = $planService;
        $this->auditService = $auditService;
        $this->ocrService = $ocrService;
        $this->attachmentService = $attachmentService;
    }

    /**
     * Show the OCR expense creation form.
     *
     * @param Group $group
     * @return \Illuminate\View\View
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

        return view('expenses.addexpenseocr', compact(
            'group',
            'members',
            'canUseOCR',
            'remainingOCRScans',
            'planName'
        ));
    }

    /**
     * Handle receipt image upload and OCR processing.
     *
     * @param Request $request
     * @param Group $group
     * @return \Illuminate\Http\JsonResponse
     */
    public function extractReceiptData(Request $request, Group $group)
    {
        try {
            // Check if user is a member of the group
            if (!$group->hasMember(auth()->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this group',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'receipt_image' => 'required|file|mimes:jpeg,png,gif,bmp,webp|max:20480',
            ]);

            // Check OCR is enabled
            if (!$this->ocrService->isEnabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR service is not configured.',
                ], 422);
            }

            // Check plan allows OCR
            if (!$this->planService->canUseOCR($group)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your plan does not allow OCR functionality.',
                ], 422);
            }

            $file = $request->file('receipt_image');

            // Generate cache key based on file hash
            $fileHash = hash_file('sha256', $file->getRealPath());
            $cacheKey = "ocr_result_{$fileHash}";

            // Extract expense data using OCR
            $extractedData = $this->ocrService->extractExpenseData($file, $cacheKey);

            // Log successful OCR extraction
            $this->auditService->logSuccess(
                'ocr_extract',
                'Expense',
                "OCR extraction completed for group '{$group->name}'",
                null,
                $group->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Receipt processed successfully',
                'data' => [
                    'vendor' => $extractedData['vendor'] ?? null,
                    'date' => $extractedData['date'] ?? null,
                    'total_amount' => $extractedData['total_amount'] ?? null,
                    'items' => $extractedData['items'] ?? [],
                    'raw_text' => $extractedData['raw_text'] ?? null,
                    'confidence' => $extractedData['confidence'] ?? 0,
                    'parse_status' => $extractedData['parse_status'] ?? 'unknown',
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('OCR extraction failed: ' . $e->getMessage(), [
                'group_id' => $group->id,
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            // Log failed OCR extraction
            try {
                $this->auditService->logFailed(
                    'ocr_extract',
                    'Expense',
                    'OCR extraction failed',
                    $e->getMessage()
                );
            } catch (\Exception $auditException) {
                Log::error('Failed to log audit: ' . $auditException->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store expense created from OCR data.
     *
     * @param Request $request
     * @param Group $group
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Group $group)
    {
        // Check if user is a member of the group
        if (!$group->hasMember(auth()->user())) {
            abort(403, 'You are not a member of this group');
        }

        // Validate the expense data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'category' => 'nullable|string|in:Accommodation,Food & Dining,Groceries,Transport,Activities,Shopping,Utilities & Services,Fees & Charges,Other',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,gif,bmp,webp|max:20480',
            'items_json' => 'nullable|json',
            'ocr_confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        try {
            // Get all members (users + contacts) for validation
            $allMembers = $group->allMembers()->get();

            // Process splits
            $validated['splits'] = $this->processSplits(
                $request->get('split_type'),
                $request->get('splits'),
                $allMembers,
                $validated['amount']
            );

            // Create the expense
            $expense = $this->expenseService->createExpense(
                $group,
                auth()->user(),
                $validated
            );

            // Log expense creation to audit trail
            $this->auditService->logSuccess(
                'create_expense_ocr',
                'Expense',
                "OCR-based expense '{$validated['title']}' ({$validated['amount']}) created in group '{$group->name}'",
                $expense->id,
                $group->id,
                [
                    'ocr_confidence' => $request->get('ocr_confidence'),
                ]
            );

            // Log activity for timeline
            ActivityService::logExpenseCreated($group, $expense);

            // Send notification to group members
            $this->notificationService->notifyExpenseCreated($expense, auth()->user());

            // Handle OCR extracted items if provided
            if (!empty($validated['items_json'])) {
                $this->expenseService->createExpenseItems($expense, $validated['items_json']);
            }

            // Handle receipt image upload if provided
            if ($request->hasFile('receipt_image')) {
                try {
                    $this->attachmentService->uploadAttachment(
                        $request->file('receipt_image'),
                        $expense,
                        'expenses'
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to upload receipt for OCR expense ' . $expense->id . ': ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('groups.expenses.show', ['group' => $group, 'expense' => $expense])
                ->with('success', 'Expense created successfully from receipt!');
        } catch (\Exception $e) {
            // Log failed expense creation
            $this->auditService->logFailed(
                'create_expense_ocr',
                'Expense',
                'Failed to create OCR-based expense',
                $e->getMessage()
            );

            Log::error('Failed to create OCR expense: ' . $e->getMessage(), [
                'group_id' => $group->id,
                'user_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create expense: ' . $e->getMessage());
        }
    }

    /**
     * Process splits based on split type.
     *
     * @param string $splitType
     * @param array|null $splits
     * @param array $members
     * @param float $totalAmount
     * @return array
     * @throws \Exception
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
