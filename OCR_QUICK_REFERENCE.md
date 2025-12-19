# OCR Feature Quick Reference

## Routes

### User-Facing Routes

```
GET  /groups/{group}/expenses-ocr/create
     Show the OCR expense creation form
     Returns: Blade view with upload interface
     Auth: Required (member of group)
     Route name: groups.expenses-ocr.create
```

```
POST /groups/{group}/expenses-ocr/extract
     Process receipt image with Google Cloud Vision
     Returns: JSON with extracted data
     Auth: Required (member of group)
     Route name: groups.expenses-ocr.extract
     Request format: multipart/form-data

     Request body:
     {
       "receipt_image": <file> // Image file
     }

     Response format (200 OK):
     {
       "success": true,
       "message": "Receipt processed successfully",
       "data": {
         "vendor": "Whole Foods Market",
         "date": "10/15/2024",
         "total_amount": 45.99,
         "items": [
           {
             "description": "Milk 2%",
             "amount": 3.99
           },
           ...
         ],
         "raw_text": "Full OCR text...",
         "confidence": 0.92,
         "parse_status": "success"
       }
     }

     Response format (422 Error):
     {
       "success": false,
       "message": "Error description"
     }
```

```
POST /groups/{group}/expenses-ocr
     Save expense created from OCR data
     Returns: Redirect to expense detail page
     Auth: Required (member of group)
     Route name: groups.expenses-ocr.store
     Request format: application/x-www-form-urlencoded

     Form fields:
     - title* (string, max 255)
     - description (string, max 1000)
     - amount* (numeric, min 0.01)
     - date* (date)
     - category (enum)
     - split_type* (equal|custom)
     - splits[{member_id}] (numeric, for custom split)
     - receipt_image (file, optional)
     - items_json (json, optional)
     - ocr_confidence (numeric, optional)
```

## Models & Database

### Reused Existing Models

```
Expense
├── payer_id → User
├── group_id → Group
├── splits → ExpenseSplit (one-to-many)
├── items → ExpenseItem (one-to-many)
├── attachments → Attachment (polymorphic)
└── comments → Comment (one-to-many)

ExpenseSplit
├── expense_id → Expense
├── user_id → User (nullable)
└── contact_id → Contact (nullable)

ExpenseItem
├── expense_id → Expense
├── description
├── amount
└── assigned_to → ExpenseItem pivot

Attachment (polymorphic)
├── attachable (morphable)
├── file_path
├── file_name
├── mime_type
└── file_size
```

No database migrations needed - uses existing tables.

## Configuration

### Enable/Disable

```php
// config/googlecloud.php
'vision' => [
    'enabled' => env('GOOGLE_CLOUD_VISION_ENABLED', false),
    ...
]
```

```bash
# .env
GOOGLE_CLOUD_VISION_ENABLED=true|false
```

### Google Cloud Setup

```bash
# .env
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_KEY_FILE=/path/to/service-account-key.json
```

### OCR Parameters

```bash
# .env
OCR_MAX_FILE_SIZE=20971520           # 20MB
OCR_LANGUAGE_HINTS=en                # Language codes
OCR_MIN_CONFIDENCE=0.7               # 0.0-1.0
OCR_CACHE_RESULTS=true               # Enable caching
OCR_CACHE_TTL=3600                   # 1 hour
OCR_MAX_CONCURRENT=5                 # Concurrent requests
```

### Plan Limits

```php
// config/googlecloud.php
'plans' => [
    'free' => [
        'monthly_ocr_scans' => 5,
        'daily_ocr_scans' => 2,
    ],
    'trip_pass' => [
        'monthly_ocr_scans' => 100,
        'daily_ocr_scans' => 20,
    ],
    'lifetime' => [
        'monthly_ocr_scans' => PHP_INT_MAX,
        'daily_ocr_scans' => PHP_INT_MAX,
    ],
]
```

## Service Methods

### OcrService

```php
// Initialize
$ocrService = app('App\Services\OcrService');

// Check if enabled
$isEnabled = $ocrService->isEnabled();
// Returns: bool

// Extract expense data from receipt
$data = $ocrService->extractExpenseData(
    $uploadedFile,  // UploadedFile instance
    $cacheKey       // Optional cache key
);
// Returns: array with vendor, date, total_amount, items, raw_text, confidence, parse_status

// Get usage statistics
$stats = $ocrService->getUsageStats($groupId, $userId);
// Returns: array with monthly_used, daily_used, monthly_limit, daily_limit
```

## Response Data Structure

### Extracted Data

```json
{
  "vendor": "Store Name",
  "date": "2024-10-15",
  "total_amount": 45.99,
  "items": [
    {
      "description": "Item description",
      "amount": 10.99
    }
  ],
  "raw_text": "Full text from OCR...",
  "confidence": 0.92,
  "parse_status": "success|no_text_detected|failed"
}
```

### Error Responses

```json
{
  "success": false,
  "message": "Error description"
}
```

## HTML Form Elements

### Upload Input

```html
<form enctype="multipart/form-data">
  <input type="file" name="receipt_image" accept="image/*" />
</form>
```

### Hidden OCR Data

```html
<input type="hidden" name="items_json" />
<input type="hidden" name="ocr_confidence" />
```

### Expense Form Fields

```html
<input type="text" name="title" required />
<input type="date" name="date" required />
<input type="number" name="amount" step="0.01" required />
<select name="category">...</select>
<textarea name="description"></textarea>
<input type="radio" name="split_type" value="equal|custom" required />
<input type="number" name="splits[{member_id}]" step="0.01" />
```

## JavaScript (Frontend)

### Extract Data

```javascript
// Upload receipt via AJAX
const formData = new FormData();
formData.append('receipt_image', file);

const response = await fetch(
  '/groups/{group}/expenses-ocr/extract',
  {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': token
    }
  }
);

const data = await response.json();
if (data.success) {
  // Use data.data.vendor, data.data.total_amount, etc.
}
```

### Display Confidence

```javascript
const confidence = data.data.confidence;
const percentage = Math.round(confidence * 100);
console.log(`OCR Confidence: ${percentage}%`);
```

## Testing

### Run Unit Tests

```bash
php artisan test tests/Unit/OcrServiceTest.php
php artisan test tests/Unit/OcrServiceTest.php --filter=testExtractTotalAmount
```

### Test OCR Extraction

```bash
# Without Google Cloud setup
GOOGLE_CLOUD_VISION_ENABLED=false

# Form will redirect to standard expense form
```

### Check Audit Logs

```
/groups/{group}/audit-logs
Search for: "ocr_extract", "create_expense_ocr"
```

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| "OCR service not enabled" | Config disabled | Set `GOOGLE_CLOUD_VISION_ENABLED=true` |
| "Key file not found" | Wrong path | Verify path in `GOOGLE_CLOUD_KEY_FILE` |
| "Invalid file type" | Unsupported format | Use JPEG, PNG, GIF, BMP, or WebP |
| "File size exceeds" | Too large | Compress image (max 20MB) |
| "Plan limit exceeded" | No more scans | Upgrade plan or wait for monthly reset |
| Poor accuracy | Blurry photo | Take clearer photo with good lighting |
| "API error" | Google Cloud issue | Check API is enabled, quotas not exceeded |

## Common Workflows

### Add Expense with OCR

1. `GET /groups/{1}/expenses-ocr/create`
2. User uploads receipt image
3. Browser: `POST /groups/{1}/expenses-ocr/extract`
4. Server: Google Cloud Vision API
5. Browser: Display extracted data
6. User: Review and edit fields
7. Browser: `POST /groups/{1}/expenses-ocr`
8. Server: Create expense
9. Redirect to expense detail

### Fallback to Standard Form

1. OCR disabled or error occurs
2. Redirect to `GET /groups/{1}/expenses/create`
3. User fills form manually
4. `POST /groups/{1}/expenses`

### View Receipt Image

1. Expense has attachments
2. Attachment created from receipt
3. Display in expense detail
4. Download link available

## Audit Trail

### Operations Logged

```
Action: ocr_extract
- User uploads receipt
- OCR processing attempt
- Success/failure with message

Action: create_expense_ocr
- Expense created from OCR
- OCR confidence logged
- Full audit trail captured
```

### Access Logs

```
/groups/{group}/audit-logs
Filter by: "ocr_extract" or "create_expense_ocr"
```

## Security Notes

### File Handling
- ✓ File type validation (images only)
- ✓ File size limits (20MB)
- ✓ Base64 encoding before sending
- ✓ Images stored encrypted

### API Security
- ✓ Service account keys in environment only
- ✓ Not committed to version control
- ✓ Restricted file permissions
- ✓ Audit logging of all operations

### Data Privacy
- ✓ Plan-based access control
- ✓ Group member verification
- ✓ Encrypted data at rest
- ✓ Temporary processing only

## Performance Tips

### Improve Accuracy
- Use clear photos with good lighting
- Keep receipt straight in frame
- Clean receipts before photographing
- Avoid glare or reflections

### Reduce Costs
- Enable caching: `OCR_CACHE_RESULTS=true`
- Don't upload same receipt twice
- Monitor API usage regularly
- Set billing alerts in Google Cloud

### Speed Up Processing
- Compress images before upload
- Use modern phone cameras (good OCR)
- Avoid oversized files
- Check network connection

## API Integration

### For Mobile Apps (Capacitor)

```javascript
// Capacitor camera
const photo = await Camera.getPhoto({
  quality: 90,
  allowEditing: false,
  resultType: ResultType.DataUrl
});

// Convert to base64 and send
const formData = new FormData();
formData.append('receipt_image', dataUriToBlob(photo.dataUrl));
```

### For External Systems

```bash
curl -X POST /groups/1/expenses-ocr/extract \
  -H "X-CSRF-TOKEN: token" \
  -F "receipt_image=@receipt.jpg" \
  -H "Authorization: Bearer token"
```

---

**Last Updated:** 2024-10-15
**Maintained By:** Development Team
**Questions?** See OCR_SETUP_GUIDE.md for detailed documentation
