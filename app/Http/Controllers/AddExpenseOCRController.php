<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

/**
 * AddExpenseOCRController
 *
 * Handles OCR-based expense creation from receipts
 * This is a placeholder for future OCR functionality
 */
class AddExpenseOCRController extends Controller
{
    /**
     * Show the OCR expense creation form.
     */
    public function create(Group $group)
    {
        $this->authorize('viewMembers', $group);
        return view('groups.expenses-ocr.create', compact('group'));
    }

    /**
     * Extract receipt data using OCR.
     */
    public function extractReceiptData(Request $request, Group $group)
    {
        $this->authorize('viewMembers', $group);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        // TODO: Implement OCR logic using a service like Google Vision API or similar
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'OCR feature not yet implemented',
            'data' => []
        ], 501);
    }

    /**
     * Store expense from OCR data.
     */
    public function store(Request $request, Group $group)
    {
        $this->authorize('viewMembers', $group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'split_type' => 'required|in:equal,itemwise,percentage',
            'members' => 'required|array|min:1',
            'members.*' => 'integer|exists:users,id',
        ]);

        // TODO: Create expense record with OCR-extracted data
        return response()->json([
            'success' => false,
            'message' => 'OCR feature not yet implemented',
            'data' => []
        ], 501);
    }
}
