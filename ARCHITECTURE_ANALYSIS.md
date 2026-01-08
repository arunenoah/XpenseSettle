# ExpenseSettle - Comprehensive Codebase Architecture Analysis

## Executive Summary

**ExpenseSettle** is a production-ready Laravel 12 group expense management platform designed to help friends, families, and travel groups split shared expenses fairly and track settlements. The application features a clean monolithic architecture with service-layer separation, Blade templating for the frontend, and Capacitor integration for mobile deployment.

---

## 1. Tech Stack Overview

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL/SQLite (local uses SQLite, production uses MySQL)
- **Authentication**: Session-based (custom PIN authentication) + Laravel Sanctum for API tokens
- **API Architecture**: RESTful routes using web routes (not API routes)
- **Queue System**: Database-backed queues for async tasks
- **File Storage**: Local filesystem with image compression (GD library)

### Frontend
- **Template Engine**: Blade (server-rendered HTML)
- **Styling**: Tailwind CSS 4.0 with `@tailwindcss/vite` plugin
- **JavaScript Framework**: Vanilla JS + Alpine.js for interactivity (no React/Vue)
- **Build Tool**: Vite 7.0+ with Laravel Vite Plugin
- **Package Manager**: NPM

### Mobile
- **Framework**: Capacitor 7.4+ (iOS & Android)
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **Native Plugins**: Camera, Geolocation, Local Notifications
- **Build Output**: iOS (xcode project) & Android (gradle project)

### External Services
- **Firebase**: Cloud Messaging for push notifications
- **Google Cloud Vision**: OCR extraction (infrastructure ready, not yet implemented)
- **Image Processing**: GD library for image compression

---

## 2. Project Folder Structure

```
expenseSettle/
├── app/
│   ├── Console/
│   │   └── Commands/          # Scheduled jobs & maintenance
│   ├── Constants/
│   │   └── ExpenseCategory.php    # 9 expense categories
│   ├── Helpers/
│   │   ├── FormatHelper.php       # Currency/number formatting
│   │   └── helpers.php            # Global helper functions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── DeviceTokenController.php  # Mobile FCM tokens
│   │   │   ├── Auth/
│   │   │   │   └── PasswordResetController.php
│   │   │   ├── Admin/
│   │   │   │   └── AuditLogController.php
│   │   │   ├── AuthController.php             # PIN login/register
│   │   │   ├── DashboardController.php        # User dashboard
│   │   │   ├── GroupController.php            # Group CRUD & member mgmt
│   │   │   ├── ExpenseController.php          # Expense CRUD
│   │   │   ├── PaymentController.php          # Settlement calculations
│   │   │   ├── SettlementController.php       # Settlement confirmations
│   │   │   ├── AdvanceController.php          # Money lent tracking
│   │   │   ├── ReceivedPaymentController.php  # Payments received
│   │   │   ├── AttachmentController.php       # File upload/download
│   │   │   ├── NotificationController.php     # Notification management
│   │   │   ├── CommentController.php          # Activity comments
│   │   │   └── AdminController.php            # Super admin panel
│   │   └── Middleware/
│   │       ├── SecurityHeaders.php            # CSP, X-Frame-Options, etc.
│   │       ├── CacheHeaders.php               # Browser caching
│   │       └── SuperAdminMiddleware.php       # Super admin access control
│   ├── Models/
│   │   ├── User.php                 # Users (Sanctum tokens, plan management)
│   │   ├── Group.php                # Group entity
│   │   ├── GroupMember.php          # Many-to-many relationship
│   │   ├── Contact.php              # Non-app members
│   │   ├── Expense.php              # Expense record
│   │   ├── ExpenseSplit.php         # Per-member split
│   │   ├── ExpenseItem.php          # Line items from receipts
│   │   ├── Payment.php              # Settlement payment tracking
│   │   ├── ReceivedPayment.php      # Received payment record
│   │   ├── Advance.php              # Money lent before expenses
│   │   ├── Attachment.php           # Polymorphic file attachments
│   │   ├── Activity.php             # Activity timeline
│   │   ├── AuditLog.php             # Compliance audit trail
│   │   ├── SettlementConfirmation.php  # Settlement approval
│   │   ├── Comment.php              # Activity comments
│   │   └── DeviceToken.php          # Firebase FCM tokens
│   ├── Providers/
│   │   └── AppServiceProvider.php   # Service registration
│   ├── Services/
│   │   ├── ExpenseService.php       # Expense business logic
│   │   ├── PaymentService.php       # Settlement calculations
│   │   ├── GroupService.php         # Group operations
│   │   ├── GroupMemberService.php   # Member management
│   │   ├── AttachmentService.php    # File handling & compression
│   │   ├── NotificationService.php  # Firebase notifications
│   │   ├── AuditService.php         # Audit logging
│   │   ├── ActivityService.php      # Timeline tracking
│   │   ├── TimelineService.php      # Timeline generation
│   │   ├── PlanService.php          # Plan tier validation
│   │   ├── FirebaseService.php      # Firebase integration
│   │   └── GamificationService.php  # Badges/achievements
│   └── Repositories/               # Query builder patterns (if used)
│
├── config/
│   ├── app.php                  # App name, timezone, debug mode
│   ├── auth.php                 # Authentication guards & providers
│   ├── database.php             # Database connections
│   ├── filesystems.php          # Storage disk configuration
│   ├── firebase.php             # Firebase credentials & settings
│   ├── security.php             # CSP & security headers
│   ├── services.php             # External service credentials
│   ├── mail.php                 # Email configuration
│   ├── queue.php                # Queue driver (database)
│   └── session.php              # Session driver (file/database)
│
├── database/
│   ├── migrations/              # 20+ database schema migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories for testing
│
├── resources/
│   ├── css/
│   │   └── app.css              # Tailwind imports & custom styles
│   ├── js/
│   │   ├── app.js               # Vite entry point
│   │   ├── bootstrap.js         # Axios configuration
│   │   └── capacitor-pdf-handler.js  # PDF handling for mobile
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php    # Master layout (Alpine.js for state)
│       ├── auth/
│       │   ├── login.blade.php
│       │   ├── register.blade.php
│       │   └── update-pin.blade.php
│       ├── dashboard.blade.php  # User home (balance cards, pending payments)
│       ├── landing-new.blade.php # Marketing landing page
│       ├── groups/
│       │   ├── index.blade.php  # List user's groups
│       │   ├── show.blade.php   # Group dashboard
│       │   ├── dashboard.blade.php # Group overview
│       │   ├── summary.blade.php # Settlement summary
│       │   ├── members.blade.php # Member management
│       │   ├── create.blade.php  # Create new group
│       │   ├── edit.blade.php    # Edit group
│       │   └── payments/
│       │       ├── history.blade.php  # Settlement history
│       │       └── ...
│       ├── expenses/
│       │   ├── create.blade.php  # Add expense form
│       │   ├── edit.blade.php    # Edit expense
│       │   ├── show.blade.php    # Expense details
│       │   └── modal.blade.php   # Expense modal view
│       ├── components/
│       │   ├── group-tabs.blade.php     # Tab navigation
│       │   ├── group-breadcrumb.blade.php
│       │   ├── group-fabs.blade.php    # Floating action buttons
│       │   ├── bar-chart.blade.php     # Chart.js wrapper
│       │   ├── donut-chart.blade.php
│       │   ├── line-chart.blade.php
│       │   ├── toast.blade.php         # Toast notifications
│       │   ├── confetti.blade.php      # Celebration animation
│       │   └── loading-skeleton.blade.php
│       └── admin/
│           ├── index.blade.php  # Admin dashboard
│           └── audit-logs/      # Audit log viewing
│
├── routes/
│   ├── web.php                  # Web routes (authenticated & public)
│   └── api.php                  # API routes (Sanctum auth for mobile)
│
├── storage/                     # Runtime storage
│   └── app/
│       ├── attachments/         # User uploads
│       └── public/              # Public assets
│
├── tests/                       # PHPUnit/Pest tests
│
├── bootstrap/                   # Framework bootstrap
│
├── vite.config.js              # Vite build config
├── package.json                # Frontend dependencies
├── composer.json               # Backend dependencies
├── capacitor.config.ts         # Capacitor mobile config
├── .env                        # Environment variables
├── .env.example                # Env template
├── artisan                     # Laravel CLI tool
└── README.md                   # Documentation
```

---

## 3. Core Data Model

### Entity Relationships

```
User (1) ──── (M) Group (via GroupMember)
    │
    ├─ (1) ──── (M) DeviceToken (Firebase tokens)
    ├─ (1) ──── (M) Activity (timeline actions)
    ├─ (1) ──── (M) AuditLog (admin logs)
    └─ (1) ──── (M) Payment (settlement payments)

Group (1) ──── (M) Expense
    ├─ (1) ──── (M) GroupMember (Users & Contacts)
    ├─ (1) ──── (M) Advance (money lent)
    ├─ (1) ──── (M) ReceivedPayment (payments received)
    └─ (1) ──── (M) SettlementConfirmation (settlement approvals)

Expense (1) ──── (M) ExpenseSplit (per-member share)
    ├─ (1) ──── (M) ExpenseItem (receipt line items)
    ├─ (1) ──── (M) Attachment (polymorphic - receipts)
    ├─ (1) ──── (M) Activity (expense created/edited/deleted)
    └─ (1) ──── (M) Comment (activity comments)

ExpenseSplit (1) ──── (M) Payment (settlement tracking)

GroupMember can reference:
    - User (for app users)
    - Contact (for non-app members)
    - Both can be NULL (flexible member system)

Attachment (polymorphic):
    - Belongs to Expense
    - Belongs to Payment
    - Belongs to Comment
    - Stored on filesystem with compression
```

### Key Models

**User** - Represents logged-in users
- Attributes: name, email, password_hash, pin_hash, admin_pin_hash, plan, plan_expires_at
- Relationships: Groups, Payments, Activities, AuditLogs, DeviceTokens
- Plans: free (5 OCR scans), trip_pass (365 days), lifetime (unlimited)

**Group** - Shared expense group
- Attributes: name, description, currency, created_by, is_active, deleted_at (soft delete)
- Relationships: Members, Expenses, Advances, Payments, SettlementConfirmations

**GroupMember** - Many-to-many with flexible membership
- Can reference a User (app member) OR Contact (non-app member)
- Attributes: role (admin/member), family_count (weighted splits), joined_at

**Expense** - Individual shared expense
- Attributes: title, amount, currency, description, date, category, status
- Relationships: ExpenseSplits, ExpenseItems, Attachments, Advances

**ExpenseSplit** - Per-member share of an expense
- Attributes: amount, percentage (for percentage-based splits), status, paid_at
- Stores the amount each person owes for an expense

**Payment** - Settlement transaction
- Tracks money transferred from payer to payee
- Attributes: amount, status (paid/unpaid), approved_by, created_at

**Attachment** - File uploads (polymorphic)
- Supports Expenses, Payments, Comments
- Stores filename, mime_type, size, compression status
- Auto-compressed to 50KB for mobile efficiency

**Activity** - Timeline tracking
- Records all group actions: expense added, payment made, member joined, etc.
- Timestamps for sorting, metadata for detailed info

**AuditLog** - Compliance trail
- Every action (CRUD) logged with user, group, action, details, timestamp
- Admin-accessible for transparency

---

## 4. Current API Endpoints

### Web Routes (Primary API - Server-Rendered)

All endpoints use server-side rendering with Blade templates, not JSON APIs.

#### Authentication Routes
```
GET  /login                 → AuthController@showLogin
POST /login                 → AuthController@login (PIN-based)
GET  /register              → AuthController@showRegister
POST /register              → AuthController@register
POST /logout                → AuthController@logout
GET  /auth/update-pin       → AuthController@showUpdatePin
PUT  /auth/update-pin       → AuthController@updatePin
```

#### Dashboard & Home
```
GET  /                      → Redirect to /dashboard or /login
GET  /landing               → Marketing landing page
GET  /dashboard             → DashboardController@index (user home)
```

#### Group Management
```
GET    /groups                              → GroupController@index (list)
GET    /groups/create                       → GroupController@create (form)
POST   /groups                              → GroupController@store (create)
GET    /groups/{group}                      → GroupController@show (details)
GET    /groups/{group}/edit                 → GroupController@edit (form)
PUT    /groups/{group}                      → GroupController@update
DELETE /groups/{group}                      → GroupController@destroy
DELETE /groups/{group}/leave                → GroupController@leaveGroup
```

#### Group Member Management
```
GET    /groups/{group}/members                                  → GroupController@members
POST   /groups/{group}/members                                  → GroupController@addMember (invite/add user)
POST   /groups/{group}/contacts                                 → GroupController@addContact (add non-app contact)
PATCH  /groups/{group}/members/{member}/family-count           → GroupController@updateFamilyCount
PATCH  /groups/{group}/contacts/{contact}/family-count         → GroupController@updateContactFamilyCount
DELETE /groups/{group}/members/{member}                         → GroupController@removeMember
```

#### Expense Management
```
GET    /groups/{group}/expenses/create              → ExpenseController@create (form)
POST   /groups/{group}/expenses                     → ExpenseController@store
GET    /groups/{group}/expenses/{expense}           → ExpenseController@show
GET    /groups/{group}/expenses/{expense}/modal     → ExpenseController@showModal (AJAX)
GET    /groups/{group}/expenses/{expense}/edit      → ExpenseController@edit
PUT    /groups/{group}/expenses/{expense}           → ExpenseController@update
DELETE /groups/{group}/expenses/{expense}           → ExpenseController@destroy
```

#### OCR-Based Expense (Infrastructure Ready)
```
GET    /groups/{group}/expenses-ocr/create         → AddExpenseOCRController@create
POST   /groups/{group}/expenses-ocr/extract         → AddExpenseOCRController@extractReceiptData (OCR)
POST   /groups/{group}/expenses-ocr                 → AddExpenseOCRController@store
```

#### Payment & Settlement
```
POST   /groups/{group}/manual-settle                → PaymentController@manualSettle
POST   /groups/{group}/settlements/confirm          → SettlementController@confirmSettlement
GET    /groups/{group}/settlements/history          → SettlementController@getSettlementHistory
GET    /groups/{group}/settlements/unsettled        → SettlementController@getUnsettledTransactions
GET    /groups/{group}/payments                     → PaymentController@groupPaymentHistory
POST   /payments/{payment}/mark-paid                → PaymentController@markPayment
POST   /splits/{split}/mark-paid                    → PaymentController@markPaid
POST   /payments/mark-paid-batch                    → PaymentController@markPaidBatch
GET    /groups/{group}/payments/member/{member}/received-payments → PaymentController@getReceivedPayments
GET    /groups/{group}/transaction-details/{type}/{id}           → PaymentController@getTransactionDetails
GET    /groups/{group}/payments/debug/{user}                     → PaymentController@debugSettlement (debug)
```

#### Advances (Money Lent)
```
POST   /groups/{group}/advances              → AdvanceController@store
DELETE /groups/{group}/advances/{advance}    → AdvanceController@destroy
```

#### Received Payments
```
POST   /groups/{group}/received-payments                              → ReceivedPaymentController@store
DELETE /groups/{group}/received-payments/{receivedPayment}            → ReceivedPaymentController@destroy
GET    /groups/{group}/members/{user}/received-payments               → ReceivedPaymentController@getForMember
```

#### Dashboard & Reports
```
GET    /groups/{group}/dashboard                    → DashboardController@groupDashboard
GET    /groups/{group}/summary                      → DashboardController@groupSummary
GET    /groups/{group}/payments/export-pdf          → PaymentController@exportHistoryPdf
GET    /groups/{group}/payments/export-member-settlements-pdf → PaymentController@exportMemberSettlementsPdf
GET    /groups/{group}/timeline/pdf                 → DashboardController@exportTimelinePdf
```

#### Attachments
```
GET    /attachments/{attachment}/download    → AttachmentController@download
GET    /attachments/{attachment}/show        → AttachmentController@show
```

#### Notifications
```
GET    /notifications                → NotificationController@index
POST   /notifications/{id}/read      → NotificationController@markAsRead
POST   /notifications/mark-all-read  → NotificationController@markAllAsRead
GET    /notifications/unread-count   → NotificationController@unreadCount
```

#### Audit Logs (Group Admin Only)
```
GET    /groups/{group}/audit-logs                   → AuditLogController@groupAuditLogs
GET    /groups/{group}/audit-logs/filter            → AuditLogController@filterByAction
GET    /groups/{group}/audit-logs/export-csv        → AuditLogController@exportCsv
```

#### Admin Routes (Super Admin Only - arun@example.com)
```
GET    /admin/verify                              → AdminController@showPinVerification
POST   /admin/verify                              → AdminController@verifyPin
GET    /admin/                                    → AdminController@index (dashboard)
POST   /admin/users/{user}/plan                   → AdminController@updateUserPlan
POST   /admin/groups/{group}/plan                 → AdminController@updateGroupPlan
POST   /admin/groups/{group}/reset-ocr            → AdminController@resetOCRCounter
POST   /admin/logout                              → AdminController@logout
```

#### Plan Management (Testing)
```
POST   /groups/{group}/increment-ocr               → GroupController@incrementOCR
POST   /groups/{group}/activate-trip-pass          → GroupController@activateTripPass (testing)
POST   /user/activate-lifetime                     → GroupController@activateLifetime (testing)
```

### API Routes (JSON Endpoints for Mobile)

Located in `routes/api.php` - minimal, Sanctum-authenticated

```
GET    /api/user                      → Return authenticated user (Sanctum middleware)
POST   /api/device-tokens             → DeviceTokenController@register (register FCM token)
GET    /api/device-tokens             → DeviceTokenController@list (list tokens)
DELETE /api/device-tokens             → DeviceTokenController@remove (deactivate token)
```

---

## 5. Authentication Mechanism

### Authentication Strategy

**PIN-Only Authentication** (no password):
- Users set a 6-digit PIN during registration
- PIN is hashed using Laravel's `Hash::make()` (Bcrypt)
- Login compares input PIN against all user hashes (linear search)
- Session stored in database/file
- **Security Note**: PIN-only is unusual; production should consider email verification

### Sanctum Tokens (Mobile)
- For API endpoints used by Capacitor app
- Token issued during login, stored in device localStorage
- Valid for 365 days by default
- Device Token endpoints track FCM registration

### Authorization
- **Session Guard**: `auth()` middleware checks session
- **Policies**: Group admin/member checks done inline in controllers
- **Super Admin**: Only `arun@example.com` via `SuperAdminMiddleware`

### PIN Management
- User PIN: Updated via `/auth/update-pin` (authenticated)
- Admin PIN: Separate hash for admin panel access (2FA-like)
- Both stored in `users.pin` and `users.admin_pin` columns

---

## 6. State Management Approach

### Frontend State Management

**Alpine.js** (Minimal Reactive Framework):
- Used in `layouts/app.blade.php` for notification state
- Stores: notification filter, unread count, list of activities
- Methods: `loadNotifications()`, filters for unread/all

**Server-Side Session** (Primary):
- User data stored in `auth()->user()`
- Group context passed via route parameters
- No client-side state management library (React Query, Zustand, etc.)

**Example: Alpine State** (from layout):
```javascript
<div x-data="{ 
    open: false, 
    filter: 'unread', 
    activities: [], 
    unreadCount: {{ $unreadCount }} 
}">
```

### Backend State Management
- **Database**: Single source of truth
- **Session**: User authentication state
- **Cache**: Not heavily utilized (room for optimization)
- **Queue**: Async job handling via database queue

### Data Flow
1. **User Action** → Form submission or AJAX request
2. **Controller** → Validates input, calls Service
3. **Service** → Business logic, database operations, logging
4. **View** → Blade template renders with fresh data
5. **Client** → Alpine.js may update local state for UI responsiveness

No centralized Redux/Zustand store; state is transient and re-fetched per request.

---

## 7. Mobile & Capacitor Configuration

### Capacitor Setup

Located in `capacitor.config.ts`:

```typescript
{
  appId: 'com.arunkumar.expensesettle.app',
  appName: 'ExpenseSettle',
  webDir: 'public',  // Serves Laravel public/ as web app
  server: {
    url: 'https://xpensesettle.on-forge.com',  // Production URL
    androidScheme: 'https',
    cleartext: false,  // HTTPS only
  },
  ios: {
    scheme: 'ExpenseSettle',
  },
  android: {
    buildOptions: {
      keystorePath: '~/.keystore/expensesettle.keystore',
      keystorePassword: env var,
      keystoreAlias: 'expensesettle',
      releaseType: 'APK',
    },
  },
  plugins: {
    SplashScreen: { ... },
    Camera: { permissions: ['camera', 'photos'] },
    Geolocation: { permissions: ['location'] },
  },
}
```

### Key Features
- **Web App Wrapper**: Capacitor wraps the Laravel web app, no separate mobile UI
- **Push Notifications**: Firebase Cloud Messaging via `@capacitor-firebase/messaging`
- **Camera**: For receipt photo capture
- **Geolocation**: For location-based features (planned)
- **Local Notifications**: For reminders

### Directories
- `/ios` - iOS Xcode project
- `/android` - Android Gradle project

### Build Process
1. Run `npm run build` to compile Vite + Tailwind
2. Copy assets to `/public`
3. Build Capacitor app: `npx cap build ios` or `npx cap build android`
4. Sign with keystore and distribute

---

## 8. Key Services (Business Logic Layer)

### ExpenseService
- Creates/updates/deletes expenses
- Handles split calculations (equal, custom, percentage)
- Validates against plan limits (OCR, attachment counts)
- Calls AuditService to log changes

### PaymentService
- Core settlement calculation algorithm
- `calculateSettlement(group, user)` - determines who owes whom
- Handles multiple currencies
- Returns balance breakdowns per person

### GroupService
- Group CRUD operations
- Member invitations (sends via notification/email)
- Role assignments (admin/member)
- Currency management

### AttachmentService
- File upload handling
- Image compression to 50KB using GD
- Storage management and cleanup
- Polymorphic attachment tracking

### NotificationService
- Sends push notifications via Firebase
- Constructs message payloads
- Handles token management

### AuditService
- Logs all actions (create, update, delete)
- Stores user, group, action type, details, timestamp
- Used for compliance and debugging

### ActivityService
- Records timeline events (non-audit)
- User-visible activity log
- Supports comments and metadata

### PlanService
- Validates plan tier access
- Checks OCR scan quota
- Checks attachment limits
- Handles plan expiration and upgrades

### FirebaseService
- Firebase credentials loading
- Service account authentication
- Message sending to FCM

---

## 9. Frontend Structure

### Blade Templating
- **Master Layout**: `layouts/app.blade.php`
  - Navigation bar (fixed top)
  - Alpine.js notification dropdown
  - CSRF token injection
  - Sanctum token exposure for mobile API calls

- **Key Pages**:
  - `dashboard.blade.php` - User home with balance cards
  - `landing-new.blade.php` - Marketing page (no auth required)
  - `groups/show.blade.php` - Group details
  - `groups/dashboard.blade.php` - Group overview

### Styling
- **Tailwind CSS 4.0**: Utility-first CSS framework
- **Custom CSS**: Minimal (Tailwind handles most styling)
- **Charts**: Chart.js 4.5.1 for visualizations

### JavaScript
- **Alpine.js**: Reactive DOM manipulation
- **Axios**: AJAX requests (via Vite bootstrap)
- **No Build Step for JS**: Inline scripts in Blade templates

### Asset Pipeline
- **Vite**: Bundles CSS and JS
- **Entry Point**: `resources/css/app.css` + `resources/js/app.js`
- **Output**: `public/build/assets/`

---

## 10. Database Schema (Key Tables)

### Core Tables
- **users** - Users with plan, PIN
- **groups** - Shared expense groups
- **group_members** - Membership (user_id OR contact_id)
- **contacts** - Non-app members
- **expenses** - Expense records
- **expense_splits** - Per-member share
- **expense_items** - Receipt line items
- **payments** - Settlement transactions
- **received_payments** - Payments recorded as received
- **advances** - Money lent before expenses
- **attachments** - File uploads (polymorphic)
- **activities** - Timeline events
- **audit_logs** - Compliance trail
- **settlement_confirmations** - Settlement approvals
- **device_tokens** - Firebase FCM tokens
- **comments** - Activity comments

### Soft Deletes
- Groups support soft deletes (deleted_at)
- Allows "undelete" without losing data

---

## 11. Security Considerations

### Implemented
- CSRF protection via middleware
- PIN-based authentication (custom)
- Sanctum token authentication (API)
- Bcrypt password hashing (for PINs)
- Role-based access control (admin/member)
- Audit logging for compliance
- Security headers middleware

### Middleware Stack
- `auth` - Requires login
- `guest` - Excludes authenticated users
- `superadmin` - Super admin only (email check)
- `SecurityHeaders` - CSP, X-Frame-Options, etc.
- `CacheHeaders` - Prevents caching of sensitive pages

### Recommendations
- Add email verification for registration
- Implement TOTP 2FA for admin access
- Use rate limiting on all public endpoints
- Add API request throttling
- Encrypt sensitive data fields (plan info, etc.)

---

## 12. Deployment Configuration

### Production Server
- **URL**: `https://xpensesettle.on-forge.com`
- **Hosting**: Laravel Forge (inferred from Capacitor config)
- **Database**: MySQL (configured in .env)
- **Queue**: Database driver (synchronous in development)
- **Mail**: Log driver (no actual email sending in dev)

### Environment Variables
- `APP_KEY` - Laravel encryption key
- `DB_*` - Database credentials
- `FIREBASE_*` - Firebase service account
- `KEYSTORE_PASSWORD` - Android signing key

### Performance Optimizations
- Image compression on upload (50KB target)
- Attachments stored on filesystem, not DB
- Database queries use eager loading (with clauses)
- Activity/Audit logs indexed for filtering

---

## 13. Main Controllers & Their Responsibilities

| Controller | Key Methods | Purpose |
|-----------|------------|---------|
| **AuthController** | login, register, updatePin | User authentication & PIN management |
| **DashboardController** | index, groupDashboard, groupSummary | Dashboard views & calculations |
| **GroupController** | CRUD, addMember, addContact, members | Group & member management |
| **ExpenseController** | CRUD, showModal | Expense management |
| **PaymentController** | groupPaymentHistory, calculateSettlement, manualSettle, exportPDF | Settlement calculations & exports |
| **SettlementController** | confirmSettlement, getHistory | Settlement confirmations |
| **AdvanceController** | store, destroy | Money lent tracking |
| **ReceivedPaymentController** | store, destroy, getForMember | Payment receipts |
| **AttachmentController** | download, show | File downloads |
| **NotificationController** | index, markAsRead, unreadCount | Activity notifications |
| **AdminController** | index, updatePlan, resetOCR | Super admin panel |
| **DeviceTokenController** (API) | register, list, remove | FCM token management |

---

## 14. Comparison: Web Routes vs API Routes

| Aspect | Web Routes | API Routes |
|--------|-----------|-----------|
| **Response Type** | HTML (Blade templates) | JSON |
| **Authentication** | Session-based | Sanctum tokens |
| **Use Case** | User-facing UI | Mobile app consumption |
| **Endpoints** | 60+ | 4 |
| **Framework** | Full Laravel MVC | Lightweight API |

---

## 15. Summary: Architecture Strengths

1. **Separation of Concerns**: Service layer isolates business logic
2. **Scalable Structure**: Multi-tenant groups, flexible membership
3. **Mobile-Ready**: Capacitor integration for native apps
4. **Audit Trail**: Compliance-ready logging
5. **Plan-Based Features**: Freemium model infrastructure
6. **File Handling**: Smart compression for mobile efficiency
7. **Multi-Currency**: Support for different currencies per group
8. **Flexible Members**: Both app users and contacts supported

---

## 16. Summary: Architecture Limitations

1. **No Caching Layer**: Redis/Memcached could optimize frequently-accessed data
2. **Limited API**: Minimal JSON API; web routes are primary
3. **Frontend Framework**: Blade + Alpine is simple but lacks modern tooling
4. **No Testing Framework**: Tests directory exists but no examples
5. **OCR Not Implemented**: Infrastructure ready, needs Google Vision integration
6. **Single Queue Driver**: Database queue is slow for heavy workloads
7. **N+1 Queries Possible**: No query optimization layer or repository pattern

---

## 17. Technology Dependencies

### Backend (PHP)
- Laravel 12
- Laravel Sanctum (API tokens)
- Laravel Dompdf (PDF generation)
- Google Cloud Vision (OCR - not yet integrated)
- Firebase Admin SDK (push notifications)

### Frontend
- Tailwind CSS 4.0
- Alpine.js 3.x
- Chart.js 4.5.1
- Axios 1.11

### Mobile
- Capacitor 7.4+
- Firebase Messaging for Capacitor
- Camera, Geolocation, Local Notifications plugins

### Database & Storage
- MySQL/SQLite
- Local filesystem (attachments)
- GD library (image compression)

---

## Conclusion

ExpenseSettle is a **production-ready, well-architected expense management platform** with:
- Clean MVC structure and service-based business logic
- Solid authentication (PIN-based) and authorization
- Mobile integration via Capacitor
- Scalable data model supporting flexible memberships
- Compliance-ready audit logging
- Multiple currency support
- Freemium pricing infrastructure

The codebase is maintainable, documented (via inline comments), and ready for deployment to a small user group for MVP validation.
