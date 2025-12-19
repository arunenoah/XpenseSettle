# ExpenseSettle - Codebase Exploration Summary

## Overview

This document summarizes the exploration of the **ExpenseSettle** application's "Add Expense" feature. The exploration was conducted on **December 18, 2025** and provides a complete architectural analysis of how expenses are created, stored, and managed.

---

## Project Context

**Project:** ExpenseSettle - Group Expense Splitter  
**Framework:** Laravel 11 + Blade Templates  
**Frontend:** Traditional form-based (no modern SPA)  
**Database:** PostgreSQL with Eloquent ORM  
**Status:** Active development (last commits: Dec 16-12, 2025)

---

## What Was Explored

### 1. Core Expense Creation Feature
- **Controller:** `ExpenseController` with create/store methods
- **Form:** 84KB Blade template with embedded JavaScript
- **Services:** ExpenseService, AttachmentService, PlanService
- **Models:** Expense, ExpenseSplit, ExpenseItem, Attachment
- **Routes:** Nested under group resource for proper scoping

### 2. Data Models & Relationships
- Expenses belong to Groups and are paid by Users
- ExpenseSplits link expenses to Users or Contacts
- ExpenseItems store line items (for OCR receipts)
- Attachments use polymorphic relationship (can attach to multiple models)
- GroupMember acts as junction table for Users and Contacts

### 3. File Upload & Attachment System
- Drag-and-drop file upload
- Image compression using PHP GD library
- Compression targets: 50KB max (from 5MB upload)
- Storage: Local filesystem (`storage/app/expenses/`)
- Database tracking: File path, size, MIME type, original name

### 4. Split Management
- **Equal Split:** Automatic division by member count (weighted by family_count)
- **Custom Split:** User-specified amounts with validation
- Both support Users and Contacts as members
- Rounding logic: Differences applied to first split

### 5. OCR Feature Infrastructure
- **UI:** Complete form UI for receipt extraction
- **Plans:** Tiered access (Free: 5 scans, Trip Pass/Lifetime: unlimited)
- **Backend:** Ready to accept extracted items as JSON
- **Missing:** Actual OCR provider integration (no extraction logic)

### 6. Authorization & Security
- Group membership required for access
- Role-based access (Payer or Admin for edit/delete)
- Validation: Server-side + client-side
- Fully paid expenses: Read-only

---

## Key Findings

### What's Fully Implemented
- Complete expense creation workflow
- Image attachment upload with compression
- Equal and custom split calculations
- Plan-based feature gating
- Audit logging for all operations
- Activity timeline tracking
- Notification system integration
- Support for both app users and non-app contacts

### What's Partially Implemented
- **OCR Integration:** UI and backend ready, but no actual OCR engine
- **PDF Support:** Form accepts PDFs, but service only handles images
- **Item-wise Splitting:** Items stored but splitting logic not implemented

### What's Missing
- OCR provider integration (Tesseract, Google Vision, AWS Textract, etc.)
- PDF handling in attachment service
- Expense templates
- Attachment versioning
- Async file uploads
- Receipt parsing logic

---

## Architecture Highlights

### Service-Oriented Design
```
Controller (validation, orchestration)
    ↓
Services (business logic)
    ├→ ExpenseService (expense operations)
    ├→ AttachmentService (file handling)
    ├→ PlanService (feature access)
    ├→ NotificationService (alerts)
    └→ AuditService (logging)
    ↓
Models (data & relationships)
    ├→ Expense
    ├→ ExpenseSplit
    ├→ ExpenseItem
    └→ Attachment
```

### Database Design
- **Normalized:** Separate tables for each entity
- **Polymorphic:** Attachments can attach to multiple models
- **Junction Tables:** GroupMember handles user/contact polymorphism
- **Cascading:** Deletes propagate to related records

### Form Architecture
- **Large Template:** 84KB Blade file (could be split)
- **Client-side Logic:** JavaScript for split calculations, file handling
- **Progressive Enhancement:** Works without JavaScript (basic functionality)
- **Inline Styling:** Tailwind CSS classes throughout

---

## Technical Debt & Improvements

### Performance
- Form size (84KB) could be optimized
- Image compression is CPU-intensive (synchronous)
- Multiple attachments processed sequentially
- Consider async file uploads

### Code Quality
- Form template could be split into components
- JavaScript logic could be extracted to external file
- Duplicate validation (client + server)

### Features
- OCR integration needs completion
- PDF support incomplete
- No expense templates
- No advanced split types (percentage-based UI missing)

### Security
- File uploads only validated by MIME type (could add magic byte check)
- No rate limiting on file uploads
- Consider additional validation for suspicious files

---

## Documentation Generated

The following analysis documents have been created:

1. **EXPENSE_FEATURE_ANALYSIS.md** (15KB)
   - Comprehensive feature documentation
   - Database schema details
   - Service layer descriptions
   - Models and relationships

2. **EXPENSE_ARCHITECTURE_DIAGRAM.md** (20KB)
   - Visual flow diagrams (ASCII)
   - Request/response flow
   - Database relationships
   - Service interactions
   - Split processing logic
   - File upload pipeline

3. **QUICK_REFERENCE.md** (10KB)
   - Quick lookup guide
   - Key files table
   - Form fields explained
   - Common issues & solutions
   - Testing checklist
   - Extension points

4. **EXPLORATION_SUMMARY.md** (This file)
   - High-level overview
   - Key findings
   - Architecture highlights

---

## File Reference

### Core Implementation Files
| File | Lines | Purpose |
|------|-------|---------|
| ExpenseController.php | 420 | Expense CRUD operations |
| ExpenseService.php | 332 | Business logic |
| AttachmentService.php | 150+ | File upload/compression |
| create.blade.php | 500+ | Form template (84KB) |
| Expense.php | 70 | Model |
| ExpenseSplit.php | 70 | Model |
| ExpenseItem.php | 50 | Model |
| Attachment.php | 40 | Model |

### Database Migrations
| Migration | Purpose |
|-----------|---------|
| 2025_12_04_030204 | Create expenses table |
| 2025_12_04_030205 | Create expense_splits table |
| 2025_12_04_030208 | Create attachments table |
| 2025_12_06_000001 | Create expense_items table |
| 2025_12_10_000004 | Recreate expense_splits (add contact support) |
| 2025_12_12_000001 | Add category to expenses |

### Routes
```
GET  /groups/{group}/expenses/create     → ExpenseController@create
POST /groups/{group}/expenses            → ExpenseController@store
GET  /groups/{group}/expenses/{expense}  → ExpenseController@show
GET  /groups/{group}/expenses/{expense}/edit → ExpenseController@edit
PUT  /groups/{group}/expenses/{expense}  → ExpenseController@update
DELETE /groups/{group}/expenses/{expense} → ExpenseController@destroy
```

---

## Data Flow Summary

### Expense Creation Flow
```
1. User navigates to /groups/{id}/expenses/create
2. Controller loads members and plan information
3. Form displays with split type toggle
4. User fills form (title, amount, split type, attachments)
5. Form submits to POST /groups/{id}/expenses
6. Controller validates all fields
7. Service creates Expense + ExpenseSplits
8. Service creates ExpenseItems (if OCR provided)
9. Service processes Attachments (compress and store)
10. Notifications sent to group members
11. Activity logged to timeline
12. Redirect to expense detail page
```

### Attachment Processing
```
1. User selects files (drag-drop or click)
2. Files validated: MIME type + size
3. AttachmentService compresses each image
4. GD library iterates quality (80% → 20%) until < 50KB
5. Compressed file stored to disk
6. Attachment record created in database
7. Returns Attachment model
```

### Split Calculation
```
Equal Split:
  amount ÷ number_of_members = per_member_share
  (weighted by family_count field)

Custom Split:
  user_specified_amounts per member
  (validated: total must equal amount ±0.01)
```

---

## Integration Points

### External Services (Used)
- Laravel's built-in file upload handling
- PHP GD library for image compression
- Laravel Storage facade for file system
- Eloquent ORM for database operations
- Blade templating engine

### External Services (Not Implemented)
- OCR provider (Google Vision, AWS Textract, Tesseract, etc.)
- Push notification service (placeholder exists)
- Payment processing (separate feature)

---

## Deployment Considerations

### Storage
- Attachments stored in `storage/app/expenses/`
- Ensure storage directory is writable
- Consider S3/cloud storage for production

### Performance
- Image compression uses server CPU
- Multiple attachments may slow form submission
- Consider async jobs for attachments

### Security
- Validate file uploads on production
- Implement rate limiting on file uploads
- Consider virus scanning for uploads

---

## Next Steps for Implementation

### If Adding PDF Support
1. Update ALLOWED_MIME_TYPES in AttachmentService
2. Modify compressImage() to skip PDFs
3. Update storage handling for different types

### If Implementing OCR
1. Choose OCR provider (Google Vision recommended)
2. Create OCRService with API integration
3. Create /api/ocr/process endpoint
4. Update frontend JavaScript
5. Test with various receipt types

### If Improving UI
1. Consider splitting 84KB form into components
2. Extract JavaScript to external file
3. Add live validation feedback
4. Improve mobile responsiveness

---

## Code Quality Standards

The codebase follows these patterns:
- **MVC:** Clear separation of concerns
- **Services:** Business logic in service classes
- **Models:** Relationships defined via Eloquent
- **Validation:** Server-side validation with Laravel rules
- **Error Handling:** Try-catch with logging
- **Authorization:** Role-based access control

---

## Testing Strategy

Current tests needed:
- Expense creation with equal split
- Expense creation with custom split
- Expense creation with attachments
- File upload validation
- Authorization checks
- Plan-based feature access
- Error handling

---

## Conclusion

The **Add Expense** feature in ExpenseSettle is a **well-architected, production-ready** expense management system with:

✅ **Strengths:**
- Clean service-oriented architecture
- Comprehensive validation
- Flexible split management
- Attachment handling with compression
- Plan-based feature gating
- Proper authorization
- Audit logging

⚠️ **Areas for Improvement:**
- OCR integration incomplete
- Form template could be optimized
- PDF support needs completion
- Async file uploads recommended
- Additional security hardening

The codebase is maintainable, testable, and ready for extension. The infrastructure for OCR is in place; it just needs the extraction logic implementation.

---

**Generated:** December 18, 2025  
**Analysis Scope:** Add Expense Feature Architecture  
**Documentation Quality:** Comprehensive

