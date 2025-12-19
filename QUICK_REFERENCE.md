# ExpenseSettle Add Expense Feature - Quick Reference Guide

## Key Files at a Glance

| Component | File Path | Size | Purpose |
|-----------|-----------|------|---------|
| Routes | `routes/web.php:82-87` | - | GET/POST for expense creation |
| Controller | `app/Http/Controllers/ExpenseController.php` | 15KB | Orchestrates expense flow |
| View | `resources/views/expenses/create.blade.php` | 84KB | Form UI + client-side logic |
| Service | `app/Services/ExpenseService.php` | 10KB | Business logic for expenses |
| Attachments | `app/Services/AttachmentService.php` | 6KB | File upload + compression |
| Plans | `app/Services/PlanService.php` | 3KB | Feature access control |
| Models | `app/Models/{Expense,ExpenseSplit,ExpenseItem,Attachment}.php` | - | Data models |
| Migrations | `database/migrations/2025_12_*` | - | Database schema |

---

## Data Flow Summary

```
User visits /groups/{id}/expenses/create
    ↓
ExpenseController->create() loads form view
    ↓
User fills form and submits
    ↓
ExpenseController->store() validates and processes
    ↓
Creates: Expense + ExpenseSplits + ExpenseItems + Attachments
    ↓
Redirects to /groups/{id}/expenses/{expense}
```

---

## Form Fields Explained

| Field | Type | Required | Validation | Notes |
|-------|------|----------|-----------|-------|
| Title | Text | Yes | Max 255 chars | "Dinner", "Movie tickets" |
| Description | Textarea | No | Max 1000 chars | Optional details |
| Amount | Number | Yes | > 0.01 | Decimal places supported |
| Date | Date | Yes | Valid date | Defaults to today |
| Category | Select | No | 9 predefined | For analytics |
| Split Type | Radio | Yes | equal/custom | Determines split logic |
| Custom Splits | Number array | No | If custom type | Per-member amounts |
| Attachments | File | No | PNG/JPEG, max 5MB | Multiple allowed |
| Items JSON | Hidden | No | JSON string | From OCR or manual |

---

## Split Type Logic

### Equal Split (Default)
- **Calculation:** `amount / number_of_members`
- **Rounding:** Applied to first member
- **Family Count:** Weighted by family_count field
- **Code:** `ExpenseService->createEqualSplits()`

### Custom Split
- **Input:** User specifies amount per member
- **Validation:** Total must equal expense amount (tolerance: 0.01)
- **Rounding:** Preserved as entered
- **Code:** `ExpenseController->processSplits('custom')`

---

## File Upload Process

```
1. User selects PNG/JPEG/PDF files (max 5MB each)
2. Laravel validates MIME type
3. AttachmentService->uploadAttachment():
   - Validates file (MIME + size)
   - Compresses image with GD library
   - Iterates quality: 80% → 20% until < 50KB
   - Converts to JPEG
   - Generates unique filename
   - Stores to storage/app/expenses/
   - Creates Attachment DB record
4. Returns Attachment model
```

### Limitations
- **Actual:** Only JPEG/PNG supported (despite UI saying PDF)
- **Max size:** 5MB upload, compressed to ~50KB
- **Format:** All converted to JPEG
- **Filename:** `{original}_{timestamp}_{random_8chars}.jpg`

---

## OCR Feature Status

### What's Implemented
- UI button to trigger OCR
- Plan-based feature gating (PlanService)
- Free tier limit: 5 scans per group
- Backend accepts items_json parameter
- ExpenseService->createExpenseItems() saves items

### What's Missing
- **No OCR engine integration** - No actual extraction logic
- **No API endpoint** for processing images
- Placeholder for future: Google Vision, AWS Textract, Tesseract, etc.

### Expected Items Structure (JSON)
```json
[
  {
    "name": "Milk",
    "quantity": 1,
    "unit_price": 3.50,
    "total_price": 3.50,
    "assigned_to": 2,
    "confidence": 95
  }
]
```

---

## Plan Tiers

| Feature | Free | Trip Pass | Lifetime |
|---------|------|-----------|----------|
| OCR Scans | 5/group | Unlimited | Unlimited |
| Attachments | 10/group | Unlimited | Unlimited |
| Duration | - | 365 days | Forever |
| Upgrade | Button | Activated | Activated |

---

## Authorization Rules

| Action | Required | Checks |
|--------|----------|--------|
| Create | Group member | `$group->hasMember()` |
| Edit | Payer OR Admin | `$expense->payer_id == auth()->id()` OR `$group->isAdmin()` |
| Delete | Payer OR Admin | Same as edit |
| View | Group member | `$group->hasMember()` |

**Note:** Fully paid expenses cannot be edited/deleted

---

## Member Types

### Users
- App members with accounts
- Stored in `users` table
- Referenced via `user_id` in ExpenseSplit

### Contacts
- Non-app members (no login)
- Stored in `contacts` table
- Referenced via `contact_id` in ExpenseSplit
- Both types can receive expense splits

---

## Validation Rules Summary

```php
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
'items_json' => 'nullable|json',
```

---

## Error Handling

```
try {
    // Create expense + splits + items + attachments
} catch (\Exception $e) {
    AuditService->logFailed()      // Log error
    return back()->with('error')   // Return to form with message
}
```

**Non-fatal errors:**
- Individual attachment failures don't stop expense creation
- Logged as warnings only

---

## Database Tables

### expenses
- id, group_id (FK), payer_id (FK), title, description, amount, split_type, category, date, status, timestamps

### expense_splits
- id, expense_id (FK), user_id/contact_id (nullable), share_amount, percentage (nullable), timestamps

### expense_items
- id, expense_id (FK), user_id (nullable), name, quantity, unit_price, total_price, timestamps
- Unique: (expense_id, name, total_price)

### attachments (polymorphic)
- id, attachable_type (string), attachable_id (int), file_path, file_name, mime_type, file_size, description, timestamps

---

## Key Service Methods

### ExpenseService
- `createExpense($group, $payer, $data)` - Create expense with splits
- `createEqualSplits($expense, $group)` - Equal split logic
- `createSplits($expense, $splits)` - Custom split logic
- `createExpenseItems($expense, $itemsJson)` - Save OCR items
- `updateExpense($expense, $data)` - Update expense
- `deleteExpense($expense)` - Delete with cascades
- `getExpenseSettlement($expense)` - Settlement calculation

### AttachmentService
- `uploadAttachment($file, $model, $directory)` - Upload + compress
- `validateFile($file)` - Check MIME + size
- `compressImage($file)` - GD-based compression
- `deleteAttachment($attachment)` - Delete from disk + DB

### PlanService
- `canUseOCR($group)` - Check OCR availability
- `getRemainingOCRScans($group)` - Get remaining scans
- `incrementOCRScan($group)` - Increment counter
- `canAddAttachment($group)` - Check attachment quota
- `getPlanName($group)` - Get display name

---

## Common Issues & Solutions

### PDF Upload "Not Allowed"
- **Issue:** UI says PDF accepted but backend rejects
- **Root cause:** AttachmentService only checks for image/jpeg, image/png
- **Fix:** Update ALLOWED_MIME_TYPES in AttachmentService

### Custom Split Validation Failed
- **Issue:** "Splits total doesn't match expense amount"
- **Root cause:** Sum of splits != expense amount (accounting for rounding)
- **Fix:** Ensure total matches within 0.01 tolerance

### OCR Not Extracting
- **Issue:** OCR button appears but no extraction happens
- **Root cause:** No OCR service implemented yet
- **Fix:** Create OCRService with API integration

### File Too Large After Upload
- **Issue:** Attachment shows inflated size
- **Root cause:** Database showing uncompressed size before processing
- **Fix:** Check file_size field in Attachment record

---

## Extension Points

### To Add New Split Type
1. Add to validation: `'split_type' => '...|new_type'`
2. Add method in ExpenseService: `createNewTypeSplits()`
3. Call from processSplits() in controller
4. Update create.blade.php form

### To Add Expense Templates
1. Create ExpenseTemplate model
2. Add UI to select template
3. Populate form fields from template
4. Allow saving current expense as template

### To Support PDF Attachments
1. Update AttachmentService ALLOWED_MIME_TYPES
2. Don't compress PDFs (store as-is)
3. Update mime type handling in compressImage()
4. Create separate download handling for PDFs

### To Implement OCR
1. Create OCRService with provider integration
2. Create API endpoint: POST /api/ocr/process
3. Add frontend JavaScript to call endpoint
4. Return JSON matching items_json structure
5. Frontend displays items for assignment

---

## Testing Checklist

- [ ] Create expense with equal split
- [ ] Create expense with custom split (total matches)
- [ ] Create expense with custom split (total mismatch) - should fail
- [ ] Upload image attachment (JPEG, PNG)
- [ ] Upload multiple attachments
- [ ] Try uploading PDF - should fail
- [ ] Try uploading file > 5MB - should fail
- [ ] Verify expense appears in group view
- [ ] Verify splits calculated correctly
- [ ] Test OCR button if implementation added
- [ ] Test category selection
- [ ] Test date picker
- [ ] Test form validation errors
- [ ] Verify attachment compression (should be < 50KB)

---

## Performance Notes

- Form is 84KB (large Blade template with embedded JS)
- Image compression is CPU-intensive (GD operations)
- Multiple attachments: Linear processing time
- Consider async attachment upload in future
- Current implementation: Sequential processing

---

## Security Considerations

- File upload validation: MIME type check
- File upload validation: Size limit (5MB)
- Form validation: Server-side with Laravel validator
- Authorization: Group membership + role checks
- CSRF: Laravel's default protection
- SQL Injection: Using Eloquent ORM
- XSS: Blade template escaping

**Potential improvements:**
- Add file type magic byte validation
- Implement rate limiting on OCR endpoint
- Add audit logging for sensitive operations

---

## Recent Changes (Git Log)

```
6c56dbb - Show attachment indicators in history page for expenses
3563fa7 - Fix 'All Expenses' tab to navigate to History page
cdbebe6 - Apply smart currency formatting to all views
572d503 - Smart currency formatting - preserve precision for small amounts
cb7e2f0 - Fix currency formatting - use helper function instead of macro
```

