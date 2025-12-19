# ExpenseSettle - Add Expense Feature Architecture Diagram

## 1. Request/Response Flow

```
USER REQUEST
    |
    v
GET /groups/{group}/expenses/create
    |
    +---> ExpenseController->create()
    |        |
    |        +---> Check group membership
    |        +---> Load group members (users + contacts)
    |        +---> Fetch plan info (canUseOCR, remainingScans, planName)
    |        +---> Render create.blade.php
    |
    v
FORM DISPLAYED TO USER
    |
    | (User fills form)
    |
    v
POST /groups/{group}/expenses (multipart/form-data)
    |
    +---> ExpenseController->store()
    |        |
    |        +---> Validate all fields
    |        |        |
    |        |        +---> title, description, amount, date, category
    |        |        +---> split_type, splits array
    |        |        +---> attachments (files)
    |        |        +---> items_json
    |        |
    |        +---> Process splits
    |        |        |
    |        |        +---> If split_type='equal': processSplits('equal')
    |        |        +---> If split_type='custom': processSplits('custom')
    |        |        +---> Validate total matches amount
    |        |
    |        +---> ExpenseService->createExpense()
    |        |        |
    |        |        +---> Create Expense record
    |        |        +---> Call createSplits()
    |        |        +---> Return Expense model
    |        |
    |        +---> Log to AuditService
    |        +---> Log to ActivityService
    |        +---> Send NotificationService alert
    |        |
    |        +---> If items_json provided:
    |        |        |
    |        |        +---> ExpenseService->createExpenseItems()
    |        |             |
    |        |             +---> Parse JSON
    |        |             +---> Create ExpenseItem per item
    |        |
    |        +---> If attachments provided:
    |        |        |
    |        |        +---> For each file:
    |        |             |
    |        |             +---> AttachmentService->uploadAttachment()
    |        |                  |
    |        |                  +---> Validate MIME type
    |        |                  +---> Validate file size
    |        |                  +---> Compress image (GD library)
    |        |                  +---> Generate unique filename
    |        |                  +---> Store to disk
    |        |                  +---> Create Attachment record
    |        |
    |        +---> Redirect to success page
    |
    v
REDIRECT /groups/{group}/expenses/{expense}
    |
    v
SUCCESS RESPONSE
```

---

## 2. Database Schema Relationships

```
EXPENSES (id, group_id, payer_id, title, amount, split_type, category, date, status)
    |
    +---> GROUPS (id, name, currency, plan, ocr_scans_used)
    |        |
    |        +---> USERS (id, name, email) [created_by]
    |        +---> GROUP_MEMBERS (id, group_id, user_id/contact_id, role, family_count)
    |        +---> CONTACTS (id, group_id, name) [non-app members]
    |
    +---> USERS (id, name, email) [payer_id]
    |
    +---> EXPENSE_SPLITS (id, expense_id, user_id/contact_id, share_amount)
    |        |
    |        +---> USERS or CONTACTS (member who owes this share)
    |        +---> PAYMENTS (payment record for this split)
    |
    +---> EXPENSE_ITEMS (id, expense_id, user_id, name, qty, unit_price, total_price)
    |        |
    |        +---> USERS (id, name) [assigned_to member]
    |
    +---> ATTACHMENTS (id, attachable_type, attachable_id, file_path, file_name)
            |
            +---> Polymorphic: Can attach to Expense, Payment, etc.
```

---

## 3. Service Layer Interactions

```
ExpenseController
    |
    +---> ExpenseService
    |        |
    |        +---> createExpense()
    |        |        |
    |        |        +---> Expense::create()
    |        |        +---> createSplits()
    |        |        |        |
    |        |        |        +---> ExpenseSplit::create() [per member]
    |        |        |
    |        |        +---> createEqualSplits() [if equal split type]
    |        |        |        |
    |        |        |        +---> Calculate total headcount (family_count)
    |        |        |        +---> Divide amount by headcount
    |        |        |        +---> Create split per member
    |        |        |
    |        |        +---> Return Expense
    |        |
    |        +---> createExpenseItems()
    |        |        |
    |        |        +---> Parse items JSON
    |        |        +---> ExpenseItem::create() [per item]
    |        |
    |        +---> updateExpense()
    |        |        |
    |        |        +---> Expense::update()
    |        |        +---> Delete and recreate splits
    |        |
    |        +---> deleteExpense()
    |                 |
    |                 +---> Delete all splits
    |                 +---> Delete all comments
    |                 +---> Delete all attachments
    |                 +---> Delete all items
    |                 +---> Delete expense
    |
    +---> AttachmentService
    |        |
    |        +---> uploadAttachment()
    |        |        |
    |        |        +---> validateFile()
    |        |        |        |
    |        |        |        +---> Check MIME type (JPEG/PNG only)
    |        |        |        +---> Check file size (max 5MB)
    |        |        |
    |        |        +---> compressImage()
    |        |        |        |
    |        |        |        +---> Load image with GD
    |        |        |        +---> Iteratively reduce quality
    |        |        |        +---> Target size: 50KB
    |        |        |        +---> Output: JPEG
    |        |        |
    |        |        +---> Storage::put()
    |        |        +---> Attachment::create()
    |        |
    |        +---> deleteAttachment()
    |                 |
    |                 +---> Storage::delete()
    |                 +---> Attachment::delete()
    |
    +---> PlanService
    |        |
    |        +---> canUseOCR()
    |        |        |
    |        |        +---> Check user plan (lifetime?)
    |        |        +---> Check group plan (trip_pass active?)
    |        |        +---> Check free limit (< 5 scans)
    |        |
    |        +---> getRemainingOCRScans()
    |        |        |
    |        |        +---> Return remaining scans for free users
    |        |        +---> Return PHP_INT_MAX for paid plans
    |        |
    |        +---> incrementOCRScan()
    |                 |
    |                 +---> Increment group.ocr_scans_used
    |
    +---> AuditService
    |        |
    |        +---> logSuccess()
    |        |        |
    |        |        +---> Create AuditLog record
    |        |
    |        +---> logFailed()
    |                 |
    |                 +---> Create AuditLog record with error
    |
    +---> ActivityService
    |        |
    |        +---> logExpenseCreated()
    |                 |
    |                 +---> Create Activity record for timeline
    |
    +---> NotificationService
             |
             +---> notifyExpenseCreated()
                      |
                      +---> Send push notifications to group members
```

---

## 4. Form Component Hierarchy

```
expenses/create.blade.php (84KB)
    |
    +---> Header Section
    |        |
    |        +---> Title "Add Expense"
    |        +---> Group name and member count
    |        +---> Step indicator (3 steps for OCR flow)
    |
    +---> Main Form (multipart/form-data)
    |        |
    |        +---> Basic Fields Section
    |        |        |
    |        |        +---> Title input (required)
    |        |        +---> Description textarea (optional)
    |        |        +---> Amount input (required)
    |        |        +---> Date picker (required)
    |        |        +---> Category select (9 options)
    |        |
    |        +---> Split Type Toggle
    |        |        |
    |        |        +---> Equal Split radio (selected by default)
    |        |        +---> Custom Split radio
    |        |
    |        +---> Custom Splits Section (hidden initially)
    |        |        |
    |        |        +---> For each member:
    |        |             |
    |        |             +---> Member name + Contact badge
    |        |             +---> Amount input
    |        |
    |        |        +---> Total allocated display
    |        |        +---> Warning if total doesn't match
    |        |
    |        +---> Attachments Section
    |        |        |
    |        |        +---> Drag-drop zone (dashed border)
    |        |        |        |
    |        |        |        +---> Hidden file input (multiple)
    |        |        |        +---> Accept: image/png, image/jpeg, application/pdf
    |        |        |        +---> Max: 5MB per file
    |        |        |
    |        |        +---> Selected files list (shown after selection)
    |        |        |
    |        |        +---> Plan status badge
    |        |        |        |
    |        |        |        +---> If Free: Show remaining OCR scans
    |        |        |        +---> If Trip Pass: Show "Unlimited"
    |        |        |        +---> If Lifetime: Show "Unlimited"
    |        |        |
    |        |        +---> OCR Section (conditional)
    |        |        |        |
    |        |        |        +---> If eligible (canUseOCR):
    |        |        |        |        |
    |        |        |        |        +---> Extract button with spinner
    |        |        |        |        +---> Info about OCR feature
    |        |        |        |        +---> Warning about remaining scans (if free)
    |        |        |        |
    |        |        |        +---> If limit reached:
    |        |        |             |
    |        |        |             +---> Lock icon
    |        |        |             +---> "OCR Limit Reached" message
    |        |        |             +---> Upgrade buttons (Trip Pass / Lifetime)
    |        |        |
    |        |        +---> Extracted Items Section (shown after OCR)
    |        |        |        |
    |        |        |        +---> Success banner
    |        |        |        +---> Items as pills with colors
    |        |        |        +---> Add item button
    |        |        |        +---> "Use Items & Auto-Split" button
    |        |        |        +---> OCR confidence warning (if low)
    |        |        |
    |        |        +---> Add Item Modal (hidden, shown on demand)
    |        |             |
    |        |             +---> Item name input
    |        |             +---> Quantity input
    |        |             +---> Unit price input
    |        |             +---> Total price input (auto-calculated)
    |        |             +---> Member assignment select
    |        |             +---> Confirm/Cancel buttons
    |        |
    |        +---> Group Info Box
    |        |        |
    |        |        +---> Display group name
    |        |        +---> Display member count
    |        |        +---> Display currency
    |        |
    |        +---> Action Buttons
    |        |        |
    |        |        +---> Submit button (Save Expense)
    |        |        +---> Cancel button (Back to group)
    |        |
    |        +---> Hidden inputs
    |             |
    |             +---> CSRF token
    |             +---> split_type (updated by JS)
    |             +---> items_json (populated by OCR/manual)
    |
    +---> Client-Side JavaScript
             |
             +---> Split type radio change handler
             |        |
             |        +---> Update UI styling
             |        +---> Show/hide custom splits section
             |
             +---> File drag-drop handler
             |        |
             |        +---> Validate files
             |        +---> Show file list
             |        +---> Show OCR section if files added
             |
             +---> OCR button click handler
             |        |
             |        +---> Disable button, show spinner
             |        +---> Send files to OCR endpoint
             |        +---> Parse response
             |        +---> Populate items pills
             |        +---> Show extracted items section
             |
             +---> Item pill interaction
             |        |
             |        +---> Click to edit assignment
             |        +---> Click X to remove item
             |
             +---> Custom split input handler
             |        |
             |        +---> Calculate total allocated
             |        +---> Show warning if mismatch
             |
             +---> Add item modal handler
                      |
                      +---> Open/close modal
                      +---> Auto-calculate total price
                      +---> Submit and add to items array
```

---

## 5. File Upload and Compression Pipeline

```
USER SELECTS FILE
    |
    v
File sent in multipart/form-data
    |
    v
ExpenseController->store() receives request
    |
    +---> $request->validate(['attachments.*' => 'file|mimes:png,jpeg,jpg,pdf|max:5120'])
    |        |
    |        v
    |    [Laravel built-in validation]
    |
    v
$attachmentService->uploadAttachment($file, $expense, 'expenses')
    |
    +---> validateFile($file)
    |        |
    |        +---> Check MIME type
    |        |        |
    |        |        +---> Allowed: image/jpeg, image/png
    |        |        +---> Fails if: application/pdf, image/gif, etc.
    |        |
    |        +---> Check file size
    |                 |
    |                 +---> MAX_UPLOAD_SIZE = 5MB
    |                 +---> Fails if > 5MB
    |
    +---> compressImage($file)
    |        |
    |        +---> Load with GD library
    |        |        |
    |        |        +---> imagecreatefromjpeg() or imagecreatefrompng()
    |        |
    |        +---> Iterative compression loop
    |        |        |
    |        |        +---> Start quality: 80%
    |        |        +---> Loop:
    |        |        |        |
    |        |        |        +---> imagejpeg($image, null, $quality)
    |        |        |        +---> Get output buffer content
    |        |        |        +---> If size > 50KB AND quality > 20%:
    |        |        |        |        |
    |        |        |        |        +---> Reduce quality by 5%
    |        |        |        |
    |        |        |        +---> Else: break loop
    |        |        |
    |        |        +---> imagedestroy($image)
    |        |        +---> Return compressed JPEG binary
    |        |
    |        +---> Result: JPEG file <= 50KB
    |
    +---> Generate unique filename
    |        |
    |        +---> Format: {original_name}_{timestamp}_{random_hash}.jpg
    |        +---> Example: receipt_1702916400_a1b2c3d4.jpg
    |
    +---> Storage::disk('local')->put($path, $compressedContent)
    |        |
    |        +---> Path: storage/app/expenses/{filename}
    |        +---> File written to disk
    |
    +---> $model->attachments()->create([...])
    |        |
    |        +---> Create Attachment record in DB:
    |        |        |
    |        |        +---> file_path: 'expenses/receipt_1702916400_a1b2c3d4.jpg'
    |        |        +---> file_name: 'receipt.png' (original name)
    |        |        +---> mime_type: 'image/jpeg' (converted)
    |        |        +---> file_size: 45000 (actual compressed size)
    |        |        +---> attachable_type: 'Expense'
    |        |        +---> attachable_id: {expense_id}
    |
    v
Return Attachment model
```

---

## 6. OCR Integration Points (Currently Unused)

```
FRONTEND: File uploaded
    |
    v
User clicks "Extract Line Items from Receipt"
    |
    v
JavaScript sends to (placeholder): /api/ocr/process
    |
    v
[MISSING: OCRService or similar]
    |
    v
[MISSING: Integration with OCR provider]
    | Potential providers:
    | - Tesseract.js (local)
    | - Google Cloud Vision API
    | - AWS Textract
    | - Azure Computer Vision
    | - CloudMersive
    |
    v
[MISSING: Receipt parsing logic]
    | Expected to extract:
    | - Item names
    | - Quantities
    | - Unit prices
    | - Total prices
    |
    v
Return JSON array:
    [
        {
            "name": "Milk",
            "quantity": 1,
            "unit_price": 3.50,
            "total_price": 3.50,
            "assigned_to": null,
            "confidence": 95
        },
        ...
    ]
    |
    v
Frontend receives JSON
    |
    v
JavaScript populates items UI
    |
    +---> Show items as pills
    +---> Allow assignment to members
    +---> Show confidence scores
    +---> Allow manual editing
    +---> Allow adding missing items
    |
    v
User clicks "Use Items & Auto-Split"
    |
    v
JavaScript builds items_json hidden input
    |
    v
Form submits with items_json
    |
    v
ExpenseController->store() receives items_json
    |
    v
ExpenseService->createExpenseItems(expense, items_json)
    |
    v
Items saved to database
```

---

## 7. Split Type Processing

```
User selects split type: Equal or Custom
    |
    v
Controller receives split_type parameter
    |
    v
processSplits(splitType, splitsArray, members, amount)
    |
    +---> If splitType = 'equal':
    |        |
    |        +---> Calculate memberCount
    |        +---> splitAmount = amount / memberCount
    |        +---> For each member:
    |        |        |
    |        |        +---> Create split with splitAmount
    |        |
    |        +---> Calculate total (account for rounding)
    |        +---> If rounding diff > 0.01:
    |        |        |
    |        |        +---> Apply diff to first split
    |        |
    |        +---> Return array[memberId => amount, ...]
    |
    +---> If splitType = 'custom':
    |        |
    |        +---> For each member:
    |        |        |
    |        |        +---> customSplits[memberId] = splitsArray[memberId]
    |        |
    |        +---> Calculate total of custom splits
    |        +---> If total != amount (tolerance 0.01):
    |        |        |
    |        |        +---> Throw exception
    |        |
    |        +---> Return customSplits
    |
    v
Splits array passed to ExpenseService->createExpense()
    |
    v
createExpense() calls createSplits(expense, splitsArray)
    |
    v
For each split in array:
    |
    +---> Get GroupMember by memberId
    |
    +---> If user member:
    |        |
    |        +---> Create ExpenseSplit(user_id, share_amount)
    |
    +---> If contact member:
    |        |
    |        +---> Create ExpenseSplit(contact_id, share_amount)
    |
    v
Splits persisted to database
```

---

## 8. Authorization & Permission Flow

```
GET /groups/{group}/expenses/create
    |
    +---> if (!$group->hasMember(auth()->user()))
    |        |
    |        v
    |    Abort 403: "You are not a member of this group"
    |
    v
    Allow access

POST /groups/{group}/expenses
    |
    +---> if (!$group->hasMember(auth()->user()))
    |        |
    |        v
    |    Abort 403: "You are not a member of this group"
    |
    v
    Create expense

GET /groups/{group}/expenses/{expense}/edit
    |
    +---> if ($expense->group_id !== $group->id)
    |        |
    |        v
    |    Abort 404: Expense not in group
    |
    +---> if (!$group->hasMember(auth()->user()))
    |        |
    |        v
    |    Abort 403: "You are not a member of this group"
    |
    +---> if ($expense->payer_id !== auth()->id() && !$group->isAdmin(auth()->user()))
    |        |
    |        v
    |    Abort 403: "You are not authorized to edit this expense"
    |
    +---> if ($expense->status === 'fully_paid')
    |        |
    |        v
    |    Abort 403: "Cannot edit a fully paid expense"
    |
    v
    Allow edit access

DELETE /groups/{group}/expenses/{expense}
    |
    +---> Same as edit checks
    |
    v
    Allow deletion
```

---

## 9. Error Handling Pipeline

```
Exception occurs during expense creation
    |
    v
try-catch block in ExpenseController->store()
    |
    +---> On success:
    |        |
    |        v
    |    Log to AuditService::logSuccess()
    |    Log to ActivityService::logExpenseCreated()
    |    Send NotificationService::notifyExpenseCreated()
    |    Redirect with success message
    |
    +---> On failure:
    |        |
    |        v
    |    Catch \Exception
    |    Log to AuditService::logFailed()
    |    Return back() with error message
    |    withInput() to retain form data
    |
    v
User sees error message or success page
```

