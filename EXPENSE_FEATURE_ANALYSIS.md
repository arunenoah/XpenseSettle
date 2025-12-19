# ExpenseSettle - Add Expense Feature Architecture Analysis

## Project Overview
**Type:** Laravel + Blade Templates (Backend) + Traditional Form-based Frontend  
**Purpose:** Split expenses among group members with advanced features like OCR receipt scanning, attachments, and itemized expenses  
**Tech Stack:** Laravel 11, Blade templates, jQuery/Vanilla JS, PostgreSQL

---

## 1. CURRENT ADD EXPENSE IMPLEMENTATION

### 1.1 Entry Point - Routes
**File:** `/routes/web.php`

```php
// Expense Management (nested under groups)
Route::get('/groups/{group}/expenses/create', [ExpenseController::class, 'create'])->name('groups.expenses.create');
Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store'])->name('groups.expenses.store');
```

### 1.2 Controller - ExpenseController
**File:** `/app/Http/Controllers/ExpenseController.php`

#### Create Method (GET /groups/{group}/expenses/create)
- Checks user is group member
- Loads all group members (users + contacts) via `$group->allMembers()->get()`
- Fetches plan information:
  - `$canUseOCR` - Boolean indicating if user can use OCR
  - `$remainingOCRScans` - Number of OCR scans left (for free users)
  - `$planName` - Current plan ('Free', 'Trip Pass', or 'Lifetime')
- Renders `expenses.create` view with these variables

#### Store Method (POST /groups/{group}/expenses)
**Validation Rules:**
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

**Processing Flow:**
1. Process splits based on split_type (equal or custom)
2. Call `ExpenseService->createExpense()` to save expense
3. Log to audit trail via `AuditService`
4. Log activity via `ActivityService`
5. Send notifications via `NotificationService`
6. If OCR items provided, create them via `ExpenseService->createExpenseItems()`
7. Process attachments via `AttachmentService->uploadAttachment()`

### 1.3 View - Create Expense Form
**File:** `/resources/views/expenses/create.blade.php` (84,000 bytes - LARGE FILE)

**Key Sections:**
1. **Expense Title Field** - Text input, required
2. **Description** - Textarea, optional
3. **Amount** - Number input with currency symbol prefix (calculated from group currency)
4. **Date** - Date picker (defaults to today)
5. **Category** - Select dropdown with 9 categories
6. **Split Type Toggle:**
   - Equal Split (radio option)
   - Custom Split (radio option)
7. **Custom Splits Section** - Hidden by default, shows per-member split inputs
8. **Attachments:**
   - Drag-and-drop file uploader
   - Accepts: PNG, JPEG, PDF (max 5MB each)
   - Plan status badge showing remaining OCR scans
   - **OCR Processing Button** (if eligible)
   - **OCR Limit Alert** (if limit reached)
9. **Extracted Items Section** - Shows when OCR completes:
   - Pills for each extracted item
   - "Add" button for missing items
   - "Use Items & Auto-Split" button
10. **Add Item Modal** - For manually adding items (qty, unit price, assignment)
11. **Group Members Info Box**
12. **Submit/Cancel Buttons**

**Client-Side JavaScript Features:**
- Split type UI toggle styling
- Custom split total calculation and validation
- File drag-and-drop handling
- OCR button interaction (shows spinner)
- Item extraction and assignment UI
- Modal for adding missing items

---

## 2. DATABASE SCHEMA

### 2.1 Expenses Table
**File:** `database/migrations/2025_12_04_030204_create_expenses_table.php`

```
id (PK)
group_id (FK → groups)
payer_id (FK → users)
title (string)
description (text, nullable)
amount (decimal:10,2)
split_type (enum: 'equal', 'custom', 'percentage')
category (string) - Added via migration 2025_12_12_000001
date (date)
status (string, default: 'pending')
timestamps
```

### 2.2 Expense Splits Table
**File:** `database/migrations/2025_12_10_000004_recreate_expense_splits_table.php`

```
id (PK)
expense_id (FK → expenses)
user_id (FK → users, nullable)
contact_id (FK → contacts, nullable)
share_amount (decimal:10,2)
percentage (decimal:5,2, nullable)
timestamps
```
**Note:** Supports both user and contact splits (polymorphic-like pattern)

### 2.3 Expense Items Table
**File:** `database/migrations/2025_12_06_000001_create_expense_items_table.php`

```
id (PK)
expense_id (FK → expenses)
user_id (FK → users, nullable) - Assignment to member
name (string)
quantity (integer, default: 1)
unit_price (decimal:10,2)
total_price (decimal:10,2)
timestamps
```
**Unique Constraint:** (expense_id, name, total_price) - Prevents duplicate items

### 2.4 Attachments Table (Polymorphic)
**File:** `database/migrations/2025_12_04_030208_create_attachments_table.php`

```
id (PK)
attachable_type (string) - Model type ('Expense', 'Payment', etc.)
attachable_id (bigint) - Model ID
file_path (string)
file_name (string)
mime_type (string)
file_size (bigint)
description (string, nullable)
timestamps
```

---

## 3. CORE SERVICES

### 3.1 ExpenseService
**File:** `/app/Services/ExpenseService.php`

**Key Methods:**

#### createExpense($group, $payer, $data)
- Creates Expense model
- Processes splits based on split_type
- Returns created Expense

#### createEqualSplits($expense, $group)
- Loads all group members with family_count
- Calculates total headcount
- Divides expense by headcount
- Creates ExpenseSplit for each member

#### createSplits($expense, $splits)
- Takes array of [groupMemberId => amount]
- For each split:
  - Finds GroupMember
  - Determines if user or contact
  - Creates ExpenseSplit with appropriate user_id or contact_id

#### createExpenseItems($expense, $itemsJson)
- Parses JSON array of items
- Creates ExpenseItem record for each
- Items structure: `{name, quantity, unit_price, total_price, assigned_to}`

#### updateExpense($expense, $data)
- Updates expense fields
- Deletes and recreates splits if updated

#### deleteExpense($expense)
- Cascades: splits, comments, attachments, items

#### getExpenseSettlement($expense)
- Calculates who owes whom for each split
- Returns array of settlement records

### 3.2 AttachmentService
**File:** `/app/Services/AttachmentService.php`

**Key Features:**

#### uploadAttachment($file, $model, $directory)
1. **Validation:**
   - Allowed MIME types: JPEG, PNG only (NOT PDF in current code - validation says PNG/JPEG only)
   - Max upload: 5MB
   - Max stored target: 50KB (compressed)

2. **Processing:**
   - Uses PHP GD library to compress image
   - Iteratively reduces quality (80% → 20%) until < 50KB
   - Converts all formats to JPEG
   - Uses unique filename: `{original}_{timestamp}_{random}.jpg`

3. **Storage:**
   - Stores to `storage/app/expenses/` directory
   - Creates Attachment record (polymorphic)
   - Returns Attachment model

**Methods:**
- `validateFile()` - MIME type and size check
- `compressImage()` - Iterative JPEG compression to 50KB
- `deleteAttachment()` - Deletes from storage and DB
- `getDownloadUrl()` - Returns route URL

### 3.3 PlanService
**File:** `/app/Services/PlanService.php`

**Plan Tiers:**
- **Free:** 5 OCR scans per group, 10 attachments per group
- **Trip Pass:** Unlimited OCR, unlimited attachments (duration-limited)
- **Lifetime:** Unlimited OCR, unlimited attachments (permanent)

**Key Methods:**
- `canUseOCR($group)` - Check if OCR available
- `getRemainingOCRScans($group)` - Returns remaining scans (free) or INT_MAX
- `incrementOCRScan($group)` - Increments counter (free only)
- `canAddAttachment($group)` - Check attachment quota
- `activateTripPass($group, $daysValid)` - Activates Trip Pass
- `activateLifetimePlan($user)` - Activates Lifetime for user
- `getPlanName($group)` - Returns display name

### 3.4 Other Services
- **NotificationService** - Sends notifications when expense created
- **ActivityService** - Logs timeline activities
- **AuditService** - Logs to audit trail for compliance

---

## 4. MODELS

### 4.1 Expense Model
```php
protected $fillable = [
    'group_id', 'payer_id', 'title', 'description', 
    'amount', 'split_type', 'category', 'date', 'status'
];

// Relationships
belongsTo(Group)
belongsTo(User, 'payer_id')
hasMany(ExpenseSplit)
hasMany(Comment)
morphMany(Attachment)
hasMany(ExpenseItem)
```

### 4.2 ExpenseSplit Model
```php
protected $fillable = [
    'expense_id', 'user_id', 'contact_id', 'share_amount', 'percentage'
];

// Relationships
belongsTo(Expense)
belongsTo(User) - nullable
belongsTo(Contact) - nullable
hasOne(Payment)
```

### 4.3 ExpenseItem Model
```php
protected $fillable = [
    'expense_id', 'user_id', 'name', 
    'quantity', 'unit_price', 'total_price'
];

// Relationships
belongsTo(Expense)
belongsTo(User, 'user_id') - Assignment to member
```

### 4.4 Attachment Model
```php
protected $fillable = [
    'file_path', 'file_name', 'mime_type', 'file_size'
];

// Relationships
morphTo('attachable') - polymorphic relationship
```

### 4.5 Group Model
```php
protected $fillable = [
    'created_by', 'name', 'icon', 'description', 
    'currency', 'plan', 'plan_expires_at', 'ocr_scans_used'
];

// Relationships
belongsToMany(User, 'group_members')
hasMany(GroupMember)
hasMany(Contact)
hasMany(Expense)
```

### 4.6 GroupMember Model
```php
protected $fillable = [
    'group_id', 'user_id', 'contact_id', 'role', 'family_count'
];

// Methods
getMember() - Returns User or Contact
getMemberName() - Returns member's name
isActiveUser() - Boolean
isContact() - Boolean
```

---

## 5. FILE UPLOAD & ATTACHMENT FLOW

### Current Implementation:
1. **Client:** Drag-drop or select PNG/JPEG/PDF files
2. **Controller:** Receives multipart/form-data
3. **Validation:** Checks MIME type, size
4. **AttachmentService:**
   - Compresses image with GD
   - Generates unique filename
   - Stores to disk (local)
   - Creates DB record
5. **Storage:** `storage/app/expenses/{filename}`
6. **Retrieval:** `GET /attachments/{attachment}/download`

### Limitations:
- No PDF handling (code says PDF but actually only JPEG/PNG)
- Max 5MB upload, compressed to ~50KB
- Only image attachments (no documents, spreadsheets)
- No versioning or file history

---

## 6. OCR FEATURE

### Current Status:
- **Infrastructure:** PlanService tracking OCR scans used
- **UI:** OCR button on create form (if eligible)
- **Backend:** `createExpenseItems()` accepts JSON of extracted items
- **Frontend:** JavaScript shows extraction UI, modal for adding items

### Missing Implementation:
- **No actual OCR engine integration** (no vendor, no API call code visible)
- Placeholder for future OCR provider (Tesseract, Google Vision, AWS Textract, etc.)
- Frontend UI ready, backend acceptance ready
- Just needs OCR extraction API integration

---

## 7. VALIDATION & AUTHORIZATION

### Authorization:
- **Create:** User must be group member
- **Edit:** Only expense payer or group admin
- **Delete:** Only expense payer or group admin
- **View:** Group member access

### Validation:
- Server-side: Laravel Request validation
- Client-side: HTML5 attributes + custom JavaScript
- Split validation: Total must match expense amount (for custom splits)

---

## 8. DEPENDENCIES & INTEGRATIONS

### Laravel Services Used:
- `Illuminate\Http\UploadedFile`
- `Illuminate\Support\Facades\Storage`
- GD Library (PHP) for image compression
- JSON encoding/decoding

### External APIs/Vendors:
- Push notifications (Firebase?)
- OCR (Not implemented yet)
- Payment processing (Not in Add Expense scope)

---

## 9. KEY ARCHITECTURAL PATTERNS

### Separation of Concerns:
- **Controller:** Request validation, orchestration
- **Service:** Business logic, complex operations
- **Model:** Data relationships
- **View:** UI presentation

### Polymorphic Relationships:
- Attachments can attach to multiple model types (Expense, Payment, etc.)

### GroupMember as Junction:
- Handles both users and contacts via polymorphic pattern
- Supports family_count for weighted equal splits

### Plan-Based Feature Flags:
- PlanService provides conditional access to features

---

## 10. ENTRY POINT FOR ADDING NEW FEATURES

### To Add Image Upload with Additional Metadata:
1. Modify Attachment migration to add new columns
2. Update ExpenseController validation
3. Update AttachmentService uploadAttachment()
4. Update create.blade.php form
5. Update show.blade.php to display metadata

### To Add OCR Integration:
1. Create OCRService with API integration
2. Create endpoint: `POST /api/ocr/process` (or webhook)
3. Frontend calls OCRService->extractItems($file)
4. Returns JSON matching ExpenseItem structure
5. Already integrated into ExpenseController->store()

### To Add PDF Support:
1. Update AttachmentService->validateFile() MIME types
2. Add PDF handling (store as-is, don't compress)
3. Update frontend accept attribute
4. Update retrieval to handle PDF MIME type

---

## 11. FILE STRUCTURE SUMMARY

```
/app
  /Models
    - Expense.php
    - ExpenseSplit.php
    - ExpenseItem.php
    - Attachment.php
    - Group.php
    - GroupMember.php
    - User.php
    - Contact.php
  /Http/Controllers
    - ExpenseController.php
  /Services
    - ExpenseService.php
    - AttachmentService.php
    - PlanService.php
    - NotificationService.php
    - AuditService.php
    - ActivityService.php

/database/migrations
  - 2025_12_04_030204_create_expenses_table.php
  - 2025_12_04_030205_create_expense_splits_table.php
  - 2025_12_04_030208_create_attachments_table.php
  - 2025_12_06_000001_create_expense_items_table.php
  - 2025_12_10_000004_recreate_expense_splits_table.php
  - 2025_12_12_000001_add_category_to_expenses.php

/resources/views/expenses
  - create.blade.php (84KB - includes split UI, OCR UI, item assignment UI)
  - show.blade.php (display expense details)
  - edit.blade.php (edit existing expense)

/routes
  - web.php (Contains expense routes)
```

---

## 12. BUSINESS LOGIC HIGHLIGHTS

### Split Calculation:
- **Equal Split:** `amount / headcount` (considers family_count)
- **Custom Split:** User-specified amounts per member
- **Percentage Split:** Supported but not in current form

### Rounding:
- All amounts rounded to 2 decimal places
- Rounding differences applied to first split

### Member Types:
- **Users:** App users with accounts
- **Contacts:** Non-app members (names only, no accounts)
- Both can be in expense splits and items

### Status Tracking:
- Expense status: 'pending', 'fully_paid'
- Split payment tracking via Payment model

---

## 13. CURRENT IMPLEMENTATION GAPS

| Feature | Status | Notes |
|---------|--------|-------|
| Image Upload | ✅ Complete | JPEG/PNG, compressed to 50KB |
| PDF Upload | ❌ UI only | Code says PDF accepted but only images processed |
| Multiple Attachments | ✅ Complete | Array handling exists |
| Attachment Metadata | ⚠️ Partial | Only file_name, mime_type, file_size |
| Item-wise Splitting | ⚠️ Partial | Items stored but not split-worthy |
| OCR Integration | ❌ Infrastructure only | PlanService ready, UI ready, no extraction logic |
| Receipt Parsing | ❌ Not implemented | |
| Expense Description | ✅ Complete | Markdown/rich text not supported |
| Category Tagging | ✅ Complete | 9 categories predefined |
| Currency Handling | ✅ Complete | Group-level currency |
| Attachment Versioning | ❌ Not implemented | |
| Expense Templates | ❌ Not implemented | |

