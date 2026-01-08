# ExpenseSettle - API Endpoints Quick Reference

## Summary Stats
- **Total Web Routes**: 60+
- **Total API Routes**: 4 (minimal, Sanctum-protected)
- **Primary Response Type**: HTML (Blade templates)
- **Secondary Response Type**: JSON (API routes)
- **Authentication**: PIN-based sessions + Sanctum tokens

---

## Quick Endpoint Lookup by Resource

### Auth (7 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/login` | AuthController@showLogin | Blade (login form) |
| POST | `/login` | AuthController@login | Redirect or error |
| GET | `/register` | AuthController@showRegister | Blade (register form) |
| POST | `/register` | AuthController@register | Redirect to dashboard |
| POST | `/logout` | AuthController@logout | Redirect to login |
| GET | `/auth/update-pin` | AuthController@showUpdatePin | Blade (PIN form) |
| PUT | `/auth/update-pin` | AuthController@updatePin | Redirect or error |

### Dashboard (3 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/` | Root handler | Redirect (dashboard/login) |
| GET | `/landing` | Static view | Blade (marketing page) |
| GET | `/dashboard` | DashboardController@index | Blade (user dashboard) |

### Groups (8 endpoints - CRUD + members)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups` | GroupController@index | Blade (groups list) |
| GET | `/groups/create` | GroupController@create | Blade (create form) |
| POST | `/groups` | GroupController@store | Redirect to group |
| GET | `/groups/{group}` | GroupController@show | Blade (group details) |
| GET | `/groups/{group}/edit` | GroupController@edit | Blade (edit form) |
| PUT | `/groups/{group}` | GroupController@update | Redirect or error |
| DELETE | `/groups/{group}` | GroupController@destroy | Redirect or error |
| DELETE | `/groups/{group}/leave` | GroupController@leaveGroup | Redirect or error |

### Group Members (5 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups/{group}/members` | GroupController@members | Blade (members list) |
| POST | `/groups/{group}/members` | GroupController@addMember | JSON/Redirect |
| POST | `/groups/{group}/contacts` | GroupController@addContact | JSON/Redirect |
| PATCH | `/groups/{group}/members/{member}/family-count` | GroupController@updateFamilyCount | JSON |
| PATCH | `/groups/{group}/contacts/{contact}/family-count` | GroupController@updateContactFamilyCount | JSON |
| DELETE | `/groups/{group}/members/{member}` | GroupController@removeMember | JSON/Redirect |

### Expenses (7 endpoints - CRUD)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups/{group}/expenses/create` | ExpenseController@create | Blade (create form) |
| POST | `/groups/{group}/expenses` | ExpenseController@store | Redirect to group |
| GET | `/groups/{group}/expenses/{expense}` | ExpenseController@show | Blade (expense details) |
| GET | `/groups/{group}/expenses/{expense}/modal` | ExpenseController@showModal | HTML (AJAX response) |
| GET | `/groups/{group}/expenses/{expense}/edit` | ExpenseController@edit | Blade (edit form) |
| PUT | `/groups/{group}/expenses/{expense}` | ExpenseController@update | Redirect or error |
| DELETE | `/groups/{group}/expenses/{expense}` | ExpenseController@destroy | JSON/Redirect |

### OCR Expenses (3 endpoints - infrastructure ready)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups/{group}/expenses-ocr/create` | AddExpenseOCRController@create | Blade (OCR form) |
| POST | `/groups/{group}/expenses-ocr/extract` | AddExpenseOCRController@extractReceiptData | JSON (OCR results) |
| POST | `/groups/{group}/expenses-ocr` | AddExpenseOCRController@store | Redirect to group |

### Payments & Settlements (11 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| POST | `/groups/{group}/manual-settle` | PaymentController@manualSettle | JSON |
| POST | `/groups/{group}/settlements/confirm` | SettlementController@confirmSettlement | JSON |
| GET | `/groups/{group}/settlements/history` | SettlementController@getSettlementHistory | Blade/JSON |
| GET | `/groups/{group}/settlements/unsettled` | SettlementController@getUnsettledTransactions | JSON |
| GET | `/groups/{group}/payments` | PaymentController@groupPaymentHistory | Blade (payment history) |
| POST | `/payments/{payment}/mark-paid` | PaymentController@markPayment | JSON |
| POST | `/splits/{split}/mark-paid` | PaymentController@markPaid | JSON |
| POST | `/payments/mark-paid-batch` | PaymentController@markPaidBatch | JSON |
| GET | `/groups/{group}/payments/member/{member}/received-payments` | PaymentController@getReceivedPayments | Blade/JSON |
| GET | `/groups/{group}/transaction-details/{type}/{id}` | PaymentController@getTransactionDetails | JSON |
| GET | `/groups/{group}/payments/debug/{user}` | PaymentController@debugSettlement | JSON (debug only) |

### Advances (2 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| POST | `/groups/{group}/advances` | AdvanceController@store | JSON |
| DELETE | `/groups/{group}/advances/{advance}` | AdvanceController@destroy | JSON |

### Received Payments (3 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| POST | `/groups/{group}/received-payments` | ReceivedPaymentController@store | JSON |
| DELETE | `/groups/{group}/received-payments/{receivedPayment}` | ReceivedPaymentController@destroy | JSON |
| GET | `/groups/{group}/members/{user}/received-payments` | ReceivedPaymentController@getForMember | JSON |

### Group Dashboard & Reports (5 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups/{group}/dashboard` | DashboardController@groupDashboard | Blade (dashboard) |
| GET | `/groups/{group}/summary` | DashboardController@groupSummary | Blade (summary) |
| GET | `/groups/{group}/payments/export-pdf` | PaymentController@exportHistoryPdf | PDF file |
| GET | `/groups/{group}/payments/export-member-settlements-pdf` | PaymentController@exportMemberSettlementsPdf | PDF file |
| GET | `/groups/{group}/timeline/pdf` | DashboardController@exportTimelinePdf | PDF file |

### Attachments (2 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/attachments/{attachment}/download` | AttachmentController@download | File download |
| GET | `/attachments/{attachment}/show` | AttachmentController@show | Image/file display |

### Notifications (4 endpoints)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/notifications` | NotificationController@index | Blade (notifications list) |
| POST | `/notifications/{id}/read` | NotificationController@markAsRead | JSON |
| POST | `/notifications/mark-all-read` | NotificationController@markAllAsRead | JSON |
| GET | `/notifications/unread-count` | NotificationController@unreadCount | JSON (count) |

### Audit Logs (3 endpoints - admin only)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/groups/{group}/audit-logs` | AuditLogController@groupAuditLogs | Blade (log list) |
| GET | `/groups/{group}/audit-logs/filter` | AuditLogController@filterByAction | Blade/JSON (filtered) |
| GET | `/groups/{group}/audit-logs/export-csv` | AuditLogController@exportCsv | CSV file |

### Admin Routes (6 endpoints - super admin only)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/admin/verify` | AdminController@showPinVerification | Blade (PIN form) |
| POST | `/admin/verify` | AdminController@verifyPin | Redirect or error |
| GET | `/admin/` | AdminController@index | Blade (admin dashboard) |
| POST | `/admin/users/{user}/plan` | AdminController@updateUserPlan | JSON |
| POST | `/admin/groups/{group}/plan` | AdminController@updateGroupPlan | JSON |
| POST | `/admin/groups/{group}/reset-ocr` | AdminController@resetOCRCounter | JSON |
| POST | `/admin/logout` | AdminController@logout | Redirect to login |

### Plan Management (3 endpoints - testing)
| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| POST | `/groups/{group}/increment-ocr` | GroupController@incrementOCR | JSON |
| POST | `/groups/{group}/activate-trip-pass` | GroupController@activateTripPass | JSON |
| POST | `/user/activate-lifetime` | GroupController@activateLifetime | JSON |

---

## API Routes (JSON endpoints for mobile)

**All require Sanctum authentication token**

| Method | Endpoint | Controller Action | Response |
|--------|----------|------------------|----------|
| GET | `/api/user` | Return user | JSON (authenticated user) |
| POST | `/api/device-tokens` | DeviceTokenController@register | JSON (token created) |
| GET | `/api/device-tokens` | DeviceTokenController@list | JSON (list of tokens) |
| DELETE | `/api/device-tokens` | DeviceTokenController@remove | JSON (success) |

---

## Middleware Guards

### Public (No Auth Required)
- `GET /` - Root redirect
- `GET /landing` - Marketing page
- `GET /login` - Login form
- `POST /login` - Login endpoint
- `GET /register` - Register form
- `POST /register` - Register endpoint

### Authenticated (Auth Required)
- All other endpoints require `auth` middleware

### Super Admin Only (arun@example.com)
- `GET /admin/verify`
- `POST /admin/verify`
- `GET /admin/`
- All other `/admin/*` endpoints

---

## Response Types Summary

| Response Type | Count | Examples |
|---------------|-------|----------|
| **Blade Template** | 35+ | Dashboard, forms, lists |
| **JSON** | 20+ | API endpoints, AJAX responses |
| **PDF File** | 3 | Export settlements, timeline |
| **CSV File** | 1 | Audit log export |
| **File Download** | 2 | Attachment download |
| **HTML (AJAX)** | 2 | Modal partials |
| **Redirect** | 20+ | Form submissions |

---

## Critical Endpoints for MVP

### User Journey
1. `POST /register` - Create account
2. `POST /login` - Authenticate
3. `POST /groups` - Create group
4. `POST /groups/{group}/members` - Add members
5. `POST /groups/{group}/expenses` - Add expense
6. `POST /groups/{group}/manual-settle` - Settle payment
7. `POST /payments/{payment}/mark-paid` - Mark as paid

### Admin Journey
1. `GET /admin/verify` - Verify PIN
2. `POST /admin/users/{user}/plan` - Upgrade user plan
3. `GET /groups/{group}/audit-logs` - View audit trail

### Mobile App Journey (using `/api` endpoints)
1. `POST /login` (web route) - Get Sanctum token
2. `POST /api/device-tokens` - Register FCM token
3. All other operations via web routes (Blade-rendered in Capacitor WebView)

---

## Performance Notes

- **N+1 Queries**: Possible in some list endpoints; use `with()` clauses
- **Caching**: Not heavily utilized; room for Redis optimization
- **Rate Limiting**: Not implemented on public endpoints (add in production)
- **File Uploads**: Auto-compressed to 50KB on attachment endpoints
- **Batch Operations**: `POST /payments/mark-paid-batch` for bulk updates

---

## Testing the Endpoints

### Quick Manual Test (curl)

```bash
# Login (get session cookie)
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"pin":"123456"}' \
  -c cookies.txt

# Create group (with session)
curl -X POST http://localhost/groups \
  -H "Content-Type: application/json" \
  -d '{"name":"Vacation","currency":"INR"}' \
  -b cookies.txt

# View dashboard
curl -X GET http://localhost/dashboard \
  -b cookies.txt
```

### API Test (with Sanctum token)

```bash
# Get token from login response (stored in localStorage on client)
TOKEN="your_sanctum_token"

# Register device token
curl -X POST http://localhost/api/device-tokens \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token":"fcm_device_token_here"}'

# Get authenticated user
curl -X GET http://localhost/api/user \
  -H "Authorization: Bearer $TOKEN"
```

---

## Error Handling

### Common HTTP Status Codes
- **200 OK** - Success
- **302 Found** - Redirect (form submission)
- **400 Bad Request** - Validation error
- **401 Unauthorized** - Not authenticated
- **403 Forbidden** - Not authorized (e.g., not group admin)
- **404 Not Found** - Resource doesn't exist
- **500 Internal Server Error** - Server error

### Error Response Examples

**Validation Error (JSON)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "pin": ["The pin field is required."]
  }
}
```

**Authorization Error (HTML redirect)**:
```html
<!-- Redirects to login -->
```

---

