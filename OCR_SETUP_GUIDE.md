# Google Cloud Vision OCR Integration Setup Guide

This guide explains how to set up and test the new OCR (Optical Character Recognition) feature for expense tracking using Google Cloud Vision API.

## Overview

The OCR feature allows users to upload receipt photos and automatically extract:
- Vendor/Store name
- Transaction date
- Total amount
- Individual line items
- Item descriptions and prices

This feature is implemented in a separate flow (`AddExpenseOCRController`) to maintain backward compatibility with the existing expense creation flow.

## Architecture

### New Components Created

1. **OcrService** (`app/Services/OcrService.php`)
   - Handles Google Cloud Vision API integration
   - Extracts and parses receipt data
   - Manages caching of OCR results
   - Validates image files

2. **AddExpenseOCRController** (`app/Http/Controllers/AddExpenseOCRController.php`)
   - Manages the OCR-enhanced expense creation flow
   - Routes: `/groups/{group}/expenses-ocr/*`
   - Provides two main endpoints:
     - `create`: Show OCR form
     - `extractReceiptData`: Process receipt image with OCR (AJAX)
     - `store`: Save expense from OCR data

3. **Blade View** (`resources/views/expenses/addexpenseocr.blade.php`)
   - Two-step form with receipt upload
   - Real-time OCR processing
   - Data review and editing interface
   - Split type selection (equal/custom)

4. **Configuration** (`config/googlecloud.php`)
   - Google Cloud Vision settings
   - OCR processing parameters
   - Plan-based feature access control

### Database Integration

The existing expense infrastructure is reused:
- Expenses, ExpenseSplits, ExpenseItems tables
- Attachments for storing receipt images
- Audit logs for tracking OCR operations

## Setup Instructions

### Step 1: Install Dependencies

```bash
composer update
```

This installs `google/cloud-vision` library added to `composer.json`.

### Step 2: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the Vision API:
   - Go to APIs & Services → Library
   - Search for "Cloud Vision API"
   - Click Enable

### Step 3: Create Service Account

1. Go to APIs & Services → Credentials
2. Click "Create Credentials" → Service Account
3. Fill in the account details
4. Grant the "Editor" role (or minimum required permissions)
5. Create a JSON key file
6. Download the JSON key file

### Step 4: Configure Environment Variables

Add these to your `.env` file:

```bash
# Google Cloud Vision Configuration
GOOGLE_CLOUD_VISION_ENABLED=true
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_KEY_FILE=/path/to/service-account-key.json

# OCR Settings
OCR_MAX_FILE_SIZE=20971520  # 20MB in bytes
OCR_LANGUAGE_HINTS=en
OCR_MIN_CONFIDENCE=0.7
OCR_CACHE_RESULTS=true
OCR_CACHE_TTL=3600
OCR_MAX_CONCURRENT=5
```

### Step 5: Store Service Account Key Securely

**Important:** Never commit the JSON key file to version control!

Recommended approaches:
1. Store the key file outside the project directory
2. Use AWS Secrets Manager or similar service
3. Environment-based configuration

For local development:
```bash
# Store key in project (add to .gitignore)
mkdir -p storage/keys
cp /path/to/downloaded-key.json storage/keys/gcp-service-account.json

# Update .env
GOOGLE_CLOUD_KEY_FILE=storage/keys/gcp-service-account.json
```

## Usage

### For Users

1. **Navigate to OCR Form**
   - Go to group → "Add Expense with OCR" (new button/link)
   - Or access: `/groups/{group}/expenses-ocr/create`

2. **Upload Receipt**
   - Click upload area or drag-and-drop receipt photo
   - Supported formats: JPEG, PNG, GIF, BMP, WebP
   - Maximum size: 20MB

3. **Review Extracted Data**
   - System automatically extracts:
     - Vendor name
     - Transaction date
     - Total amount
     - Individual items (if available)
   - Edit any field that needs correction
   - Confidence score displayed

4. **Confirm and Save**
   - Select expense category
   - Choose split type (equal/custom)
   - Enter any additional notes
   - Save expense

### Plan Limitations

Different plans have different OCR scan limits:

```
Free Plan:        5 monthly scans, 2 daily scans
Trip Pass:        100 monthly scans, 20 daily scans
Lifetime:         Unlimited scans
```

These are configured in `config/googlecloud.php` and enforced by `PlanService`.

## Routes

### OCR-Specific Routes

```php
GET    /groups/{group}/expenses-ocr/create
       → Show OCR expense form
       → Route name: groups.expenses-ocr.create

POST   /groups/{group}/expenses-ocr/extract
       → Process receipt with Google Cloud Vision (AJAX)
       → Route name: groups.expenses-ocr.extract
       → Request: multipart/form-data with receipt_image file
       → Response: JSON with extracted data

POST   /groups/{group}/expenses-ocr
       → Store expense from OCR data
       → Route name: groups.expenses-ocr.store
       → Request: Form with expense details and OCR data
```

## Testing

### Unit Tests

Run OCR service tests:

```bash
php artisan test tests/Unit/OcrServiceTest.php
```

Tests cover:
- File validation
- Text parsing and extraction
- Amount detection
- Vendor name detection
- Date extraction
- Line item parsing

### Manual Testing

#### Without Google Cloud Setup (Simulation)

1. Disable OCR in config:
   ```php
   GOOGLE_CLOUD_VISION_ENABLED=false
   ```

2. The form will redirect to standard expense form

#### With Google Cloud Setup

1. Enable OCR:
   ```php
   GOOGLE_CLOUD_VISION_ENABLED=true
   ```

2. Test receipt extraction:
   - Upload a clear receipt photo
   - Verify extracted data accuracy
   - Check confidence score

3. Test plan limits:
   - Run multiple scans to trigger limit
   - Verify appropriate error messages

### Browser Console Testing

After uploading a receipt, check browser console for:
- OCR API response
- Extracted data structure
- Confidence percentage

## Implementation Details

### OCR Data Flow

```
1. User uploads receipt image
   ↓
2. AddExpenseOCRController.extractReceiptData()
   ↓
3. OcrService.extractExpenseData()
   ↓
4. File validation
   ↓
5. Convert to base64
   ↓
6. Google Cloud Vision API request
   ↓
7. Parse text annotations
   ↓
8. Extract structured data (vendor, date, items, total)
   ↓
9. Return JSON response to frontend
   ↓
10. Populate form fields
    ↓
11. User reviews and confirms
    ↓
12. Submit to AddExpenseOCRController.store()
    ↓
13. Create expense with OCR data
```

### Parsing Logic

The `OcrService` uses regex patterns to extract:

**Total Amount:**
- Patterns: "Total: $X.XX", "TOTAL: $X.XX", "$X.XX Total"
- Fallback: Last large number found

**Vendor Name:**
- Extracted from first non-metadata line (top of receipt)
- Filters out addresses, phone numbers

**Date:**
- Patterns: MM/DD/YYYY, YYYY-MM-DD, "Month DD, YYYY"

**Line Items:**
- Filters headers, footers, metadata
- Pairs descriptions with amounts
- Limits to 50 items per receipt

### Error Handling

- File validation (type, size)
- Google Cloud API errors
- Parsing failures (graceful degradation)
- OCR confidence tracking
- Plan limits enforcement

All errors logged to audit trail with context.

## Troubleshooting

### "OCR service is not enabled"

**Cause:** `GOOGLE_CLOUD_VISION_ENABLED` is false

**Solution:**
```bash
# .env
GOOGLE_CLOUD_VISION_ENABLED=true
GOOGLE_CLOUD_KEY_FILE=/path/to/key.json
```

### "Google Cloud Vision key file not found"

**Cause:** Key file path is incorrect or file doesn't exist

**Solution:**
```bash
# Verify file exists
ls -la /path/to/service-account-key.json

# Update .env with correct path
GOOGLE_CLOUD_KEY_FILE=/absolute/path/to/key.json
```

### "Invalid file type"

**Cause:** Uploaded file is not a supported image format

**Solution:** Use JPEG, PNG, GIF, BMP, or WebP files only

### "File size exceeds maximum"

**Cause:** Image is larger than 20MB

**Solution:** Compress image before uploading (use phone camera compression)

### Poor OCR accuracy

**Cause:** Receipt is blurry, angled, or has poor lighting

**Solution:**
- Take clearer photos
- Ensure good lighting
- Keep receipt straight in frame
- Clean receipt before photographing

## Security Considerations

1. **Key File Protection**
   - Never commit to version control
   - Store with restricted file permissions
   - Use secrets management service in production

2. **API Quotas**
   - Set up billing alerts in Google Cloud
   - Monitor API usage in Cloud Console
   - Implement rate limiting if needed

3. **Data Privacy**
   - Receipt images are temporary
   - Extracted data is stored encrypted
   - Images deleted after attachment upload
   - Comply with privacy regulations

4. **Plan Enforcement**
   - OCR scans tracked per group/user
   - Plan limits enforced server-side
   - Audit logs track all OCR operations

## Performance Optimization

### Caching

OCR results are cached by default (1 hour TTL):
- Cache key: `ocr_result_{file_hash}`
- Reduces API costs
- Disable with: `OCR_CACHE_RESULTS=false`

### Concurrent Requests

Max 5 concurrent OCR requests (configurable):
- Prevents overwhelming Google Cloud
- Adjust with: `OCR_MAX_CONCURRENT`

### Image Size Limits

- Upload limit: 5MB (attachment compression)
- OCR processing limit: 20MB (raw)
- Quality automatically adjusted during compression

## Future Enhancements

1. **Smart Item-to-Member Mapping**
   - Auto-assign items to members
   - ML-based suggestions

2. **Multiple Language Support**
   - Config language hints per group
   - Auto-detect receipt language

3. **Receipt Template Recognition**
   - Store/supermarket specific parsing
   - Improved accuracy for known formats

4. **Batch Processing**
   - Upload multiple receipts
   - Async job processing

5. **Manual OCR Editing UI**
   - Item-by-item review
   - Confidence-based highlighting
   - Quick correction interface

## Additional Resources

- [Google Cloud Vision API Docs](https://cloud.google.com/vision/docs)
- [Google Cloud PHP Client Library](https://googleapis.dev/php/Google/Cloud/Vision)
- [OCR Feature Architecture](./EXPENSE_ARCHITECTURE_DIAGRAM.md)
- [Expense Feature Analysis](./EXPENSE_FEATURE_ANALYSIS.md)

## Support

For issues or questions:
1. Check troubleshooting section above
2. Review browser console for JavaScript errors
3. Check Laravel logs: `storage/logs/laravel.log`
4. Review audit logs: `/groups/{group}/audit-logs`

---

**Note:** This is a separate implementation from the standard expense creation flow. Both flows coexist, allowing testing and gradual rollout of OCR functionality.
