# Future Plans - ExpenseSettle

## 1. OCR Integration for Receipt Processing

### Overview
Add optical character recognition (OCR) capability to automatically extract expense details from receipt images.

### Implementation Strategy

#### Phase 1: Basic OCR (Google Cloud Vision)
- **Cost**: $0.0015 per receipt
- **Timeline**: ~2-3 hours development
- **Features**:
  - Upload receipt image
  - Extract text automatically
  - Manual verification before saving
  - Cost estimate for 1000 receipts: **$1.50/month**

**Implementation Path**:
```
1. Set up Google Cloud Vision API credentials
2. Create OCR wrapper service in app/Services/OCRService.php
3. Add receipt upload endpoint
4. Parse extracted text for amount, date, vendor
5. Display extracted data for user verification
6. Save as new expense after confirmation
```

#### Phase 2: AI-Powered Smart Extraction (Optional Premium)
- **Cost**: $0.003-0.01 per receipt (Gemini/Claude)
- **Timeline**: ~8-10 hours development
- **Features**:
  - Auto-categorize expenses
  - Extract vendor details
  - Identify trip/group automatically
  - Handle multiple currencies
  - Cost estimate: **$3-10/1000 receipts**

**Recommended AI Provider**: Claude AI (Anthropic)
- Best accuracy for financial documents
- Reasonable pricing
- No rate limiting issues

#### Phase 3: Premium Feature
- **Monetization**: Charge users $0.05 per smart receipt
- **Benefit**: Pay-per-use model, users decide value
- **Features**:
  - Auto-categorization
  - Smart splitting suggestions
  - Multi-language support
  - Handwriting recognition

### Recommended Approach: Hybrid

```
Step 1: Basic OCR (Google Vision) - $0.0015
        ↓
Step 2: Simple regex parsing for amount/date
        ↓
Step 3: Manual verification by user
        ↓
Step 4: Optional AI enrichment if user wants it - $0.01
```

### Cost Estimate

| Scenario | Monthly Users | Receipts/Month | Basic OCR Cost | AI Cost | Total |
|----------|---------------|----------------|----------------|---------|-------|
| Small (10 users) | 10 | 100 | $0.15 | $1 | $1.15 |
| Medium (50 users) | 50 | 500 | $0.75 | $5 | $5.75 |
| Large (100 users) | 100 | 1000 | $1.50 | $10 | $11.50 |

### Files to Create/Modify

1. **New Files**:
   - `app/Services/OCRService.php` - OCR wrapper
   - `app/Http/Controllers/OCRController.php` - Receipt upload endpoint
   - `database/migrations/add_ocr_fields_to_expenses.php` - Store OCR metadata
   - `resources/views/expenses/upload-receipt.blade.php` - UI for upload

2. **Modified Files**:
   - `app/Models/Expense.php` - Add OCR fields
   - `routes/web.php` - Add OCR endpoints
   - `.env.example` - Add OCR API credentials

### Environment Variables Needed

```env
# Google Cloud Vision (for basic OCR)
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_CLOUD_KEY_FILE=path/to/service-account.json

# Claude AI (for premium smart extraction)
CLAUDE_API_KEY=your-api-key

# Feature flags
ENABLE_OCR=true
ENABLE_AI_OCR=false
OCR_CONFIDENCE_THRESHOLD=0.85
```

### Implementation Timeline

- **Tomorrow**: Research & API setup
- **Day 2-3**: Basic Google Vision integration
- **Day 4-5**: Testing & refinement
- **Later**: AI enhancement & premium tier

### Testing Checklist

- [ ] Upload receipt image
- [ ] Extract text correctly
- [ ] Parse amount, date, vendor
- [ ] Handle multiple file formats (JPG, PNG, PDF)
- [ ] Error handling for failed extractions
- [ ] Verify extracted data before save
- [ ] Cost monitoring dashboard

### Considerations

**Security**:
- Store receipt images securely
- Encrypt sensitive data
- Implement rate limiting

**Performance**:
- Use async job queue for processing
- Cache results to avoid re-processing
- Implement pagination for receipts

**User Experience**:
- Show extraction progress
- Allow manual correction
- Suggest category based on extracted data
- One-click save for quick processing

### Next Steps

1. Set up Google Cloud project
2. Create OCR service wrapper
3. Build receipt upload UI
4. Implement expense creation from OCR
5. Add testing & error handling
6. Document API usage
7. Plan Phase 2 (AI enhancement)

---

**Status**: Planned for implementation
**Last Updated**: Dec 10, 2025
**Estimated Effort**: 20-25 hours total
