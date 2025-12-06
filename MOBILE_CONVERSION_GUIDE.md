# Technical Guide: Converting ExpenseSettle Laravel Web App to Mobile Applications

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Technology Approach Comparison](#technology-approach-comparison)
3. [Backend API Transformation](#backend-api-transformation)
4. [Mobile App Feature Requirements](#mobile-app-feature-requirements)
5. [Recommended Implementation: React Native + Expo](#recommended-implementation-react-native--expo)
6. [Timeline and Effort Estimation](#timeline-and-effort-estimation)
7. [Tools and Libraries](#tools-and-libraries)
8. [Common Pitfalls to Avoid](#common-pitfalls-to-avoid)

---

## 1. Architecture Overview

### Current State (Web Application)
Your ExpenseSettle application currently uses a monolithic Laravel architecture where:
- **Backend**: Laravel 12 handles routing, business logic, database operations, and view rendering
- **Frontend**: Server-side rendered Blade templates with Vite + Tailwind CSS for styling
- **Authentication**: Session-based with custom 6-digit PIN system
- **State Management**: Server-side with traditional page reloads

### Target State (Mobile + API)
The mobile architecture will follow a client-server separation pattern:

```
┌─────────────────────────────────────────────────┐
│           Mobile Apps (Client)                  │
│  ┌──────────────┐      ┌──────────────┐        │
│  │   iOS App    │      │ Android App  │        │
│  │ (React Native)│      │(React Native)│        │
│  └──────────────┘      └──────────────┘        │
│         │                      │                │
│         └──────────┬───────────┘                │
│                    │ HTTPS/JSON                 │
└────────────────────┼────────────────────────────┘
                     │
┌────────────────────┼────────────────────────────┐
│           Laravel Backend (API Server)          │
│                    │                             │
│  ┌─────────────────▼──────────────┐             │
│  │   API Routes (/api/v1/*)       │             │
│  │   - JWT Authentication          │             │
│  │   - JSON Response Format        │             │
│  │   - CORS Enabled                │             │
│  │   - Rate Limiting               │             │
│  └─────────────┬───────────────────┘             │
│                │                                 │
│  ┌─────────────▼───────────────┐                │
│  │  Controllers + Services     │                │
│  │  (Existing Business Logic)  │                │
│  └─────────────┬───────────────┘                │
│                │                                 │
│  ┌─────────────▼───────────────┐                │
│  │  Models + Database          │                │
│  │  (Existing Data Layer)      │                │
│  └─────────────────────────────┘                │
└─────────────────────────────────────────────────┘
```

### Key Architectural Changes

#### 1. Separation of Concerns
- **Backend (Laravel)**: Pure API server handling business logic, data validation, and database operations
- **Frontend (Mobile)**: Native mobile UI handling presentation, user interactions, and local state
- **Communication**: RESTful JSON API over HTTPS

#### 2. Authentication Flow
```
Current (Web):                  New (Mobile):
Session + Cookies        →      JWT Token-based
Server-side sessions     →      Stateless API
6-digit PIN login        →      PIN → JWT token
```

#### 3. Data Flow
```
Web:                            Mobile:
Request → Controller →          Request → API endpoint →
  View rendering                  JSON response →
                                   Mobile renders UI
```

---

## 2. Technology Approach Comparison

### A. React Native + Expo (RECOMMENDED)

**Overview**: JavaScript/TypeScript framework using React paradigms for cross-platform mobile development with Expo tooling.

#### Pros
- **Single codebase** for iOS and Android (95%+ code reuse)
- **Reusable skills**: If you know React (currently using Vite), learning curve is minimal
- **Expo ecosystem**: Simplified development with built-in tools for camera, biometrics, notifications
- **Hot reload**: Fast development iterations
- **Large community**: Extensive libraries and resources
- **Web reusability**: Can potentially reuse some validation logic and utilities
- **OTA updates**: Deploy updates without app store review (for non-native changes)
- **Cost-effective**: One development team for both platforms

#### Cons
- **Bundle size**: Larger app size compared to native (25-40MB)
- **Performance**: Slightly lower than native for complex animations/computations
- **Native dependencies**: Some packages may require ejecting from Expo
- **Bridge overhead**: Communication between JS and native layers adds latency

#### Time Estimate
- **Setup & Infrastructure**: 1-2 weeks
- **Core Features**: 6-8 weeks
- **Testing & Refinement**: 2-3 weeks
- **Total**: 9-13 weeks (2.5-3 months)

#### Developer Skills Required
- JavaScript/TypeScript proficiency
- React fundamentals (hooks, components, state management)
- Basic understanding of mobile UX patterns
- API integration experience
- Optional: Native module development for custom features

#### Code Reusability from Web App
- **Validation logic**: ~40% (can reuse validation rules)
- **API integration**: ~30% (HTTP client patterns)
- **Business logic utilities**: ~50% (date formatting, calculations)
- **UI components**: ~0% (completely different paradigm)

#### Performance Characteristics
- **Startup time**: 2-4 seconds on mid-range devices
- **UI rendering**: 50-60 FPS for most operations
- **Memory usage**: 80-150MB baseline
- **Network performance**: Excellent with proper caching
- **Offline capability**: Good with AsyncStorage/SQLite

#### Community & Resources
- **GitHub stars**: 120k+
- **NPM packages**: 50,000+ React Native compatible
- **Stack Overflow**: 250k+ questions
- **Documentation**: Excellent official docs and tutorials
- **Job market**: High demand for React Native developers

---

### B. Flutter (Dart)

**Overview**: Google's UI toolkit using Dart language, compiles to native code for both platforms.

#### Pros
- **Near-native performance**: Compiles to ARM/x64 machine code
- **Beautiful UI**: Material Design and Cupertino widgets out-of-the-box
- **Hot reload**: Instant UI updates during development
- **Single codebase**: 95%+ code sharing between platforms
- **Growing ecosystem**: Google's backing ensures long-term support
- **Excellent documentation**: Well-structured learning resources
- **Widget system**: Composable UI components

#### Cons
- **New language**: Must learn Dart (different from PHP/JavaScript)
- **Larger app size**: 15-30MB minimum
- **Smaller community**: Compared to React Native
- **Platform-specific features**: May require writing platform channels
- **Limited web reusability**: Cannot reuse existing JavaScript code

#### Time Estimate
- **Learning Dart**: 2-3 weeks (if coming from PHP/JS)
- **Setup & Infrastructure**: 1-2 weeks
- **Core Features**: 8-10 weeks
- **Testing & Refinement**: 3-4 weeks
- **Total**: 14-19 weeks (3.5-4.5 months)

#### Developer Skills Required
- Dart language proficiency (new to learn)
- Object-oriented programming
- Understanding of widget composition
- State management (Provider, Riverpod, or Bloc)
- Platform-specific knowledge for advanced features

#### Code Reusability from Web App
- **Validation logic**: ~20% (need to rewrite in Dart)
- **API integration**: ~20% (different HTTP client)
- **Business logic**: ~30% (logic translation required)
- **UI components**: ~0% (completely different)

#### Performance Characteristics
- **Startup time**: 1-2 seconds on mid-range devices
- **UI rendering**: Consistent 60 FPS, can reach 120 FPS
- **Memory usage**: 60-120MB baseline
- **Compilation**: Ahead-of-time (AOT) for production
- **Animation**: Smooth, GPU-accelerated

#### Community & Resources
- **GitHub stars**: 165k+
- **Pub packages**: 40,000+
- **Stack Overflow**: 180k+ questions
- **Documentation**: Excellent with interactive examples
- **Job market**: Growing demand

---

### C. Native Development (Swift + Kotlin)

**Overview**: Platform-specific development using Apple's Swift for iOS and Google's Kotlin for Android.

#### Pros
- **Best performance**: Direct access to platform APIs, no bridge overhead
- **Full platform access**: Immediate access to new OS features
- **Optimal UX**: Platform-native look and feel
- **No framework limitations**: Complete control over implementation
- **Security**: Better for sensitive operations (biometrics, encryption)
- **App size**: Smallest possible footprint

#### Cons
- **Two codebases**: Separate development for iOS and Android
- **Double development time**: Everything built twice
- **Two skill sets required**: Swift AND Kotlin expertise
- **Higher maintenance cost**: Updates needed in two places
- **Longer development cycle**: No code sharing between platforms
- **Larger team needed**: Ideally separate iOS and Android developers

#### Time Estimate (per platform)
- **iOS (Swift)**:
  - Setup: 1 week
  - Core Features: 8-10 weeks
  - Testing: 3-4 weeks
  - Total: 12-15 weeks

- **Android (Kotlin)**:
  - Setup: 1 week
  - Core Features: 8-10 weeks
  - Testing: 3-4 weeks
  - Total: 12-15 weeks

- **Combined**: 24-30 weeks (6-7.5 months) if developed sequentially
- **Parallel development**: 12-16 weeks with 2 developers

#### Developer Skills Required
- **iOS**: Swift, UIKit/SwiftUI, Xcode, iOS SDK
- **Android**: Kotlin, Jetpack Compose, Android Studio, Android SDK
- RESTful API integration for both
- Platform-specific design patterns (MVVM, Clean Architecture)

#### Code Reusability from Web App
- **Between platforms**: ~5% (mostly constants and config)
- **From web**: ~15% (API endpoint definitions, business rules documentation)
- **Shared**: API contracts only

#### Performance Characteristics
- **Startup time**: <1 second
- **UI rendering**: 60-120 FPS natively
- **Memory usage**: 40-80MB baseline
- **Battery efficiency**: Most optimized
- **Offline capability**: Excellent with Core Data/Room

#### Community & Resources
- **iOS**: Massive Apple developer community, WWDC resources
- **Android**: Extensive Google documentation, Android Dev Summit
- **Stack Overflow**: Combined 500k+ questions
- **Job market**: High demand for native developers (higher salaries)

---

### D. Ionic/Cordova (Web-Based Wrapper)

**Overview**: Web application wrapped in a native container, using HTML/CSS/JavaScript.

#### Pros
- **Web technology reuse**: Use existing HTML/CSS/JavaScript skills
- **High code reuse**: Can share code with web version
- **Familiar development**: Standard web development workflow
- **Quick prototyping**: Fast to build initial version
- **Single codebase**: Web, iOS, and Android from one source
- **Plugin ecosystem**: Cordova plugins for native features

#### Cons
- **Poor performance**: Runs in WebView, not truly native
- **Subpar UX**: Doesn't feel native, uncanny valley effect
- **Limited animations**: Janky scrolling and transitions
- **Battery drain**: Higher than native alternatives
- **Deprecation concerns**: Cordova losing community support
- **App store rejection risk**: May not meet quality guidelines
- **Memory intensive**: WebView overhead significant

#### Time Estimate
- **Setup**: 1 week
- **Core Features**: 4-6 weeks (reusing web components)
- **Native integration**: 2-3 weeks (camera, biometrics)
- **Performance optimization**: 2-4 weeks
- **Total**: 9-14 weeks (2.5-3.5 months)

#### Developer Skills Required
- HTML, CSS, JavaScript (existing skills)
- Ionic Framework or Cordova
- Basic native development for plugins
- Web performance optimization

#### Code Reusability from Web App
- **HTML/CSS**: ~60% potentially reusable
- **JavaScript logic**: ~70% reusable
- **Styling**: ~50% (needs mobile adaptations)
- **Overall**: Highest reuse but worst quality

#### Performance Characteristics
- **Startup time**: 3-6 seconds
- **UI rendering**: 30-40 FPS average
- **Memory usage**: 120-200MB (WebView overhead)
- **Battery impact**: Higher than native
- **User experience**: Noticeably non-native

#### Community & Resources
- **Declining**: Many developers migrating to React Native/Flutter
- **Ionic**: Still maintained but smaller community
- **Cordova**: In maintenance mode
- **Capacitor**: Modern alternative to Cordova (better option)

---

### Quick Comparison Matrix

| Criteria | React Native + Expo | Flutter | Native (Swift+Kotlin) | Ionic/Cordova |
|----------|-------------------|---------|----------------------|---------------|
| **Development Time** | 2.5-3 months | 3.5-4.5 months | 6-7.5 months | 2.5-3.5 months |
| **Performance** | ⭐⭐⭐⭐ (85%) | ⭐⭐⭐⭐⭐ (95%) | ⭐⭐⭐⭐⭐ (100%) | ⭐⭐ (60%) |
| **User Experience** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Code Reuse** | 95% iOS/Android | 95% iOS/Android | 5% iOS/Android | 70% web/mobile |
| **Learning Curve** | Easy (if know React) | Medium (learn Dart) | Steep (2 languages) | Easy (web skills) |
| **Community Size** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Maintenance Cost** | Low | Low | High (2 codebases) | Medium |
| **App Size** | 25-40 MB | 15-30 MB | 10-20 MB | 30-50 MB |
| **Future-Proof** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| **Hiring Developers** | Easy | Medium | Medium | Hard |
| **Hot Reload** | ✅ | ✅ | Limited | ✅ |
| **Platform Features** | Good | Good | Excellent | Limited |

---

## 3. Backend API Transformation

### 3.1 Current Routes Analysis

Your current web routes need to be transformed into RESTful API endpoints:

#### Current Web Routes
```php
// Authentication
GET  /login
POST /login
GET  /register
POST /register
POST /logout

// Dashboard
GET  /dashboard
GET  /groups/{group}/dashboard

// Groups
GET    /groups
POST   /groups
GET    /groups/{group}
PUT    /groups/{group}
DELETE /groups/{group}

// Group Members
POST   /groups/{group}/members
DELETE /groups/{group}/members/{member}
DELETE /groups/{group}/leave

// Expenses
POST   /groups/{group}/expenses
GET    /groups/{group}/expenses/{expense}
PUT    /groups/{group}/expenses/{expense}
DELETE /groups/{group}/expenses/{expense}

// Advances
POST   /groups/{group}/advances
DELETE /groups/{group}/advances/{advance}

// Payments
POST /payments/{payment}/mark-paid
POST /splits/{split}/mark-paid
GET  /groups/{group}/payments

// Attachments
GET /attachments/{attachment}/download
GET /attachments/{attachment}/show
```

### 3.2 New API Endpoint Structure

Create new API routes in `routes/api.php`:

```php
<?php
// File: routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    DashboardController,
    GroupController,
    ExpenseController,
    AdvanceController,
    PaymentController,
    AttachmentController,
    UserController
};

// Public routes (no authentication)
Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-pin', [AuthController::class, 'forgotPin']);
});

// Protected routes (JWT authentication required)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // User Profile
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/change-pin', [UserController::class, 'changePin']);
    Route::post('/user/avatar', [UserController::class, 'updateAvatar']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Groups
    Route::apiResource('groups', GroupController::class);
    Route::prefix('groups/{group}')->group(function () {
        // Group specific routes
        Route::get('/dashboard', [DashboardController::class, 'groupDashboard']);
        Route::get('/summary', [GroupController::class, 'summary']);

        // Members
        Route::get('/members', [GroupController::class, 'members']);
        Route::post('/members', [GroupController::class, 'addMember']);
        Route::delete('/members/{member}', [GroupController::class, 'removeMember']);
        Route::post('/leave', [GroupController::class, 'leaveGroup']);

        // Expenses
        Route::apiResource('expenses', ExpenseController::class);

        // Advances
        Route::get('/advances', [AdvanceController::class, 'index']);
        Route::post('/advances', [AdvanceController::class, 'store']);
        Route::delete('/advances/{advance}', [AdvanceController::class, 'destroy']);

        // Payments
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/history', [PaymentController::class, 'history']);
        Route::post('/settlements', [PaymentController::class, 'calculateSettlement']);
    });

    // Payments (global)
    Route::prefix('payments')->group(function () {
        Route::post('/{payment}/mark-paid', [PaymentController::class, 'markPaid']);
        Route::put('/{payment}/mark-paid', [PaymentController::class, 'updatePayment']);
    });

    // Splits
    Route::post('/splits/{split}/mark-paid', [PaymentController::class, 'markSplitPaid']);

    // Attachments
    Route::prefix('attachments')->group(function () {
        Route::post('/', [AttachmentController::class, 'upload']);
        Route::get('/{attachment}', [AttachmentController::class, 'show']);
        Route::get('/{attachment}/download', [AttachmentController::class, 'download']);
        Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
    });

    // Receipt OCR
    Route::post('/ocr/scan-receipt', [ExpenseController::class, 'scanReceipt']);

    // Notifications
    Route::get('/notifications', [UserController::class, 'notifications']);
    Route::put('/notifications/{notification}/read', [UserController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [UserController::class, 'markAllNotificationsRead']);
    Route::post('/notifications/register-device', [UserController::class, 'registerDevice']);
});
```

### 3.3 Authentication Changes: Session to JWT

#### Current Authentication (Session-based)
```php
// Current in AuthController.php
Auth::login($user, $request->boolean('remember'));
$request->session()->regenerate();
```

#### New Authentication (JWT with Laravel Sanctum)

**Step 1: Install Laravel Sanctum**
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Step 2: Configure Sanctum**
```php
// File: config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    'expiration' => 60 * 24 * 30, // 30 days
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

**Step 3: Create API Auth Controller**
```php
// File: app/Http/Controllers/Api/V1/AuthController.php

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/'
            ],
            'pin' => 'required|string|digits:6|confirmed|unique:users',
            'device_name' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'pin' => $validated['pin'],
        ]);

        // Create token for the device
        $deviceName = $validated['device_name'] ?? 'mobile-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Login with 6-digit PIN.
     */
    public function login(Request $request)
    {
        // Rate limiting
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'status' => 'error',
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                'retry_after' => $seconds,
            ], 429);
        }

        $validated = $request->validate([
            'pin' => 'required|string|digits:6',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('pin', $validated['pin'])->first();

        if (!$user) {
            RateLimiter::hit($key, $decayMinutes * 60);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid PIN',
                'errors' => [
                    'pin' => ['The provided PIN is incorrect.']
                ]
            ], 401);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($key);

        // Revoke old tokens (optional - for single device login)
        // $user->tokens()->delete();

        // Create new token
        $deviceName = $validated['device_name'] ?? 'mobile-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }

    /**
     * Get authenticated user information.
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $request->user(),
            ]
        ]);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Refresh token (create new token, revoke old).
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $deviceName = $request->input('device_name', 'mobile-device');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }
}
```

**Step 4: Update User Model**
```php
// File: app/Models/User.php

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; // Add HasApiTokens

    // ... rest of your model
}
```

### 3.4 CORS Configuration

Mobile apps make cross-origin requests, so CORS must be configured:

```php
// File: config/cors.php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        // Add your mobile app domains if using web views
        '*', // For development only, restrict in production
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
```

**Update .env**
```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:19006
SESSION_DRIVER=cookie
SESSION_DOMAIN=.yourdomain.com
```

### 3.5 Rate Limiting for Mobile

```php
// File: app/Http/Kernel.php

protected $middlewareGroups = [
    'api' => [
        'throttle:api',  // Default rate limit
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $middlewareAliases = [
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
];
```

```php
// File: app/Providers/RouteServiceProvider.php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot()
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('uploads', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });
}
```

### 3.6 API Versioning Strategy

**URL-based versioning** (Recommended for mobile apps):
```
/api/v1/groups
/api/v2/groups  // Future version with breaking changes
```

**Header-based versioning** (Alternative):
```
Accept: application/vnd.expensesettle.v1+json
```

**Version Controller Structure**:
```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── V1/
│           │   ├── AuthController.php
│           │   ├── GroupController.php
│           │   └── ExpenseController.php
│           └── V2/  // Future version
│               └── ...
```

### 3.7 Response Format Standardization

Create a consistent API response structure:

```php
// File: app/Http/Responses/ApiResponse.php

<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response.
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Error response.
     */
    public static function error(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response.
     */
    public static function paginated($data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ], 200);
    }
}
```

**Usage in Controllers**:
```php
use App\Http\Responses\ApiResponse;

public function index()
{
    $groups = Group::paginate(20);
    return ApiResponse::paginated($groups, 'Groups retrieved successfully');
}

public function store(Request $request)
{
    $group = Group::create($validated);
    return ApiResponse::success($group, 'Group created successfully', 201);
}
```

### 3.8 API Resource Transformers

Use Laravel API Resources for consistent data formatting:

```php
// File: app/Http/Resources/V1/GroupResource.php

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'created_by' => $this->created_by,
            'member_count' => $this->members()->count(),
            'total_expenses' => $this->expenses()->sum('amount'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional relationships
            'members' => GroupMemberResource::collection($this->whenLoaded('members')),
            'expenses' => ExpenseResource::collection($this->whenLoaded('expenses')),
        ];
    }
}
```

```php
// File: app/Http/Resources/V1/ExpenseResource.php

<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'date' => $this->date->toDateString(),
            'status' => $this->status,
            'split_type' => $this->split_type,
            'payer' => new UserResource($this->whenLoaded('payer')),
            'splits' => ExpenseSplitResource::collection($this->whenLoaded('splits')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'items' => ExpenseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

**Usage**:
```php
// Single resource
return ApiResponse::success(
    new GroupResource($group),
    'Group retrieved successfully'
);

// Collection
return ApiResponse::success(
    GroupResource::collection($groups),
    'Groups retrieved successfully'
);
```

### 3.9 API Validation

Create Form Requests for mobile API validation:

```php
// File: app/Http/Requests/Api/V1/CreateExpenseRequest.php

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateExpenseRequest extends FormRequest
{
    public function authorize()
    {
        // Check if user is group member
        return $this->route('group')->hasMember($this->user());
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'split_type' => 'required|in:equal,custom',
            'splits' => 'nullable|array',
            'splits.*' => 'nullable|numeric|min:0',
            'items_json' => 'nullable|json',
        ];
    }

    /**
     * Handle failed validation for API (return JSON).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Handle failed authorization for API.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to perform this action',
            ], 403)
        );
    }
}
```

### 3.10 Error Handling for API

```php
// File: app/Exceptions/Handler.php

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

public function register()
{
    $this->renderable(function (AuthenticationException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }
    });

    $this->renderable(function (NotFoundHttpException $e, $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
            ], 404);
        }
    });

    $this->renderable(function (\Throwable $e, $request) {
        if ($request->is('api/*')) {
            $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production')
                    ? 'Server error'
                    : $e->getMessage(),
                'debug' => app()->environment('production') ? null : [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ],
            ], $statusCode);
        }
    });
}
```

---

## 4. Mobile App Feature Requirements

### 4.1 Biometric Authentication

**Purpose**: Allow users to login with fingerprint/Face ID instead of PIN for faster access.

**Implementation Strategy**:

**React Native (Expo)**:
```javascript
// Using expo-local-authentication
import * as LocalAuthentication from 'expo-local-authentication';

async function authenticateWithBiometrics() {
  // Check if biometrics are available
  const hasHardware = await LocalAuthentication.hasHardwareAsync();
  const isEnrolled = await LocalAuthentication.isEnrolledAsync();

  if (!hasHardware || !isEnrolled) {
    return { success: false, error: 'Biometrics not available' };
  }

  // Authenticate
  const result = await LocalAuthentication.authenticateAsync({
    promptMessage: 'Login to ExpenseSettle',
    fallbackLabel: 'Use PIN',
    disableDeviceFallback: false,
  });

  return result;
}
```

**Backend Changes**: None required - biometrics only unlock locally stored token/PIN

**Storage**:
```javascript
import * as SecureStore from 'expo-secure-store';

// Store PIN securely after first login
await SecureStore.setItemAsync('user_pin', pin);

// Retrieve and auto-login after biometric success
const pin = await SecureStore.getItemAsync('user_pin');
```

### 4.2 Push Notifications

**Purpose**: Notify users of new expenses, payment requests, and group activities.

**Architecture**:
```
Mobile App → FCM/APNS → Laravel Backend → Queue → Notification Service → Push to devices
```

**Laravel Backend Setup**:

```bash
composer require laravel/notification-channels
composer require laravel/fcm-notification-channel  # For Firebase
```

```php
// File: app/Notifications/NewExpenseNotification.php

<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class NewExpenseNotification extends Notification
{
    private $expense;
    private $group;

    public function __construct($expense, $group)
    {
        $this->expense = $expense;
        $this->group = $group;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class, 'database'];
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setData([
                'type' => 'new_expense',
                'expense_id' => $this->expense->id,
                'group_id' => $this->group->id,
            ])
            ->setNotification([
                'title' => "New expense in {$this->group->name}",
                'body' => "{$this->expense->payer->name} added {$this->expense->title} - \${$this->expense->amount}",
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'new_expense',
            'expense_id' => $this->expense->id,
            'group_id' => $this->group->id,
            'message' => "New expense: {$this->expense->title}",
        ];
    }
}
```

**Mobile (React Native with Expo)**:
```javascript
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';

// Configure notification handler
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

// Register for push notifications
async function registerForPushNotifications() {
  let token;

  if (Constants.isDevice) {
    const { status: existingStatus } = await Notifications.getPermissionsAsync();
    let finalStatus = existingStatus;

    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }

    if (finalStatus !== 'granted') {
      return null;
    }

    token = (await Notifications.getExpoPushTokenAsync()).data;
  }

  return token;
}

// Send token to backend
async function sendPushTokenToBackend(token) {
  await api.post('/notifications/register-device', {
    push_token: token,
    device_type: Platform.OS, // 'ios' or 'android'
  });
}
```

**Backend Endpoint**:
```php
// File: app/Http/Controllers/Api/V1/UserController.php

public function registerDevice(Request $request)
{
    $validated = $request->validate([
        'push_token' => 'required|string',
        'device_type' => 'required|in:ios,android',
    ]);

    $request->user()->update([
        'push_token' => $validated['push_token'],
        'device_type' => $validated['device_type'],
    ]);

    return ApiResponse::success(null, 'Device registered for push notifications');
}
```

### 4.3 Offline Sync

**Purpose**: Allow users to view data and queue actions while offline, sync when online.

**Strategy**: Optimistic UI updates with background sync

**React Native Implementation**:

```javascript
// Local database with WatermelonDB or AsyncStorage
import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';

class OfflineManager {
  // Check network status
  static async isOnline() {
    const state = await NetInfo.fetch();
    return state.isConnected;
  }

  // Cache API responses
  static async cacheData(key, data) {
    await AsyncStorage.setItem(`cache_${key}`, JSON.stringify({
      data,
      timestamp: Date.now(),
    }));
  }

  // Retrieve cached data
  static async getCachedData(key, maxAge = 3600000) { // 1 hour default
    const cached = await AsyncStorage.getItem(`cache_${key}`);
    if (!cached) return null;

    const { data, timestamp } = JSON.parse(cached);
    if (Date.now() - timestamp > maxAge) return null;

    return data;
  }

  // Queue offline actions
  static async queueAction(action) {
    const queue = await this.getActionQueue();
    queue.push({
      ...action,
      timestamp: Date.now(),
      id: `${Date.now()}_${Math.random()}`,
    });
    await AsyncStorage.setItem('action_queue', JSON.stringify(queue));
  }

  static async getActionQueue() {
    const queue = await AsyncStorage.getItem('action_queue');
    return queue ? JSON.parse(queue) : [];
  }

  // Process queued actions when back online
  static async syncQueue() {
    const queue = await this.getActionQueue();
    const results = [];

    for (const action of queue) {
      try {
        // Execute the queued API call
        await api[action.method](action.endpoint, action.data);
        results.push({ id: action.id, success: true });
      } catch (error) {
        results.push({ id: action.id, success: false, error });
      }
    }

    // Remove successful actions from queue
    const remainingQueue = queue.filter(action =>
      !results.find(r => r.id === action.id && r.success)
    );
    await AsyncStorage.setItem('action_queue', JSON.stringify(remainingQueue));

    return results;
  }
}

// Usage
async function createExpense(expenseData) {
  const isOnline = await OfflineManager.isOnline();

  if (isOnline) {
    // Normal API call
    const response = await api.post('/expenses', expenseData);
    return response.data;
  } else {
    // Queue for later
    await OfflineManager.queueAction({
      method: 'post',
      endpoint: '/expenses',
      data: expenseData,
    });

    // Return optimistic response
    return {
      id: `temp_${Date.now()}`,
      ...expenseData,
      status: 'pending_sync',
    };
  }
}

// Listen for network changes
NetInfo.addEventListener(state => {
  if (state.isConnected) {
    OfflineManager.syncQueue();
  }
});
```

### 4.4 Camera Integration for Receipt OCR

**Purpose**: Scan receipt photos and extract expense items automatically.

**React Native (Expo) Implementation**:

```javascript
import * as ImagePicker from 'expo-image-picker';
import * as ImageManipulator from 'expo-image-manipulator';

async function captureReceipt() {
  // Request camera permissions
  const { status } = await ImagePicker.requestCameraPermissionsAsync();

  if (status !== 'granted') {
    alert('Camera permission is required to scan receipts');
    return null;
  }

  // Launch camera
  const result = await ImagePicker.launchCameraAsync({
    mediaTypes: ImagePicker.MediaTypeOptions.Images,
    allowsEditing: true,
    aspect: [3, 4],
    quality: 0.8,
  });

  if (result.canceled) return null;

  // Compress image before upload
  const manipulatedImage = await ImageManipulator.manipulateAsync(
    result.assets[0].uri,
    [{ resize: { width: 1024 } }],
    { compress: 0.7, format: ImageManipulator.SaveFormat.JPEG }
  );

  return manipulatedImage;
}

async function processReceiptOCR(imageUri) {
  // Create FormData for multipart upload
  const formData = new FormData();
  formData.append('receipt', {
    uri: imageUri,
    type: 'image/jpeg',
    name: 'receipt.jpg',
  });

  // Send to backend for OCR processing
  const response = await api.post('/ocr/scan-receipt', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });

  return response.data.data; // Extracted items
}
```

**Backend OCR Processing**:

```php
// File: app/Http/Controllers/Api/V1/ExpenseController.php

public function scanReceipt(Request $request)
{
    $request->validate([
        'receipt' => 'required|image|max:10240', // 10MB max
    ]);

    try {
        // Use Google Cloud Vision or AWS Textract for OCR
        $ocrService = app(OcrService::class);
        $extractedData = $ocrService->processReceipt($request->file('receipt'));

        return ApiResponse::success([
            'items' => $extractedData['items'],
            'total' => $extractedData['total'],
            'merchant' => $extractedData['merchant'],
            'date' => $extractedData['date'],
        ], 'Receipt processed successfully');

    } catch (\Exception $e) {
        return ApiResponse::error('Failed to process receipt', null, 500);
    }
}
```

```php
// File: app/Services/OcrService.php

<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class OcrService
{
    public function processReceipt($file)
    {
        // Save file temporarily
        $path = $file->store('temp');
        $fullPath = storage_path('app/' . $path);

        // Initialize Google Cloud Vision
        $imageAnnotator = new ImageAnnotatorClient([
            'credentials' => config('services.google.vision_key_path')
        ]);

        $image = file_get_contents($fullPath);
        $response = $imageAnnotator->textDetection($image);
        $texts = $response->getTextAnnotations();

        if ($texts) {
            $fullText = $texts[0]->getDescription();
            $parsed = $this->parseReceiptText($fullText);
        }

        // Cleanup
        unlink($fullPath);
        $imageAnnotator->close();

        return $parsed;
    }

    private function parseReceiptText(string $text): array
    {
        // Implement parsing logic
        // Extract items, prices, total, date, merchant
        // This is simplified - actual implementation needs sophisticated parsing

        $lines = explode("\n", $text);
        $items = [];
        $total = 0;

        foreach ($lines as $line) {
            // Look for item patterns (name followed by price)
            if (preg_match('/(.+?)\s+\$?([\d.]+)$/', $line, $matches)) {
                $items[] = [
                    'name' => trim($matches[1]),
                    'amount' => (float) $matches[2],
                ];
            }

            // Look for total
            if (preg_match('/total.*?\$?([\d.]+)/i', $line, $matches)) {
                $total = (float) $matches[1];
            }
        }

        return [
            'items' => $items,
            'total' => $total,
            'merchant' => $this->extractMerchant($text),
            'date' => $this->extractDate($text),
        ];
    }
}
```

### 4.5 File Upload Handling

**Mobile (React Native)**:

```javascript
import * as DocumentPicker from 'expo-document-picker';

async function uploadAttachment(expenseId) {
  // Pick document
  const result = await DocumentPicker.getDocumentAsync({
    type: ['image/*', 'application/pdf'],
    copyToCacheDirectory: true,
  });

  if (result.type === 'cancel') return null;

  // Prepare FormData
  const formData = new FormData();
  formData.append('attachment', {
    uri: result.uri,
    type: result.mimeType,
    name: result.name,
  });
  formData.append('expense_id', expenseId);

  // Upload with progress tracking
  const response = await api.post('/attachments', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    onUploadProgress: (progressEvent) => {
      const percentCompleted = Math.round(
        (progressEvent.loaded * 100) / progressEvent.total
      );
      console.log(`Upload progress: ${percentCompleted}%`);
    },
  });

  return response.data;
}
```

**Backend**:

```php
// File: app/Http/Controllers/Api/V1/AttachmentController.php

public function upload(Request $request)
{
    $request->validate([
        'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        'expense_id' => 'required|exists:expenses,id',
    ]);

    $expense = Expense::findOrFail($request->expense_id);

    // Check authorization
    if (!$expense->group->hasMember(auth()->user())) {
        return ApiResponse::error('Unauthorized', null, 403);
    }

    $attachmentService = app(AttachmentService::class);
    $attachment = $attachmentService->uploadAttachment(
        $request->file('attachment'),
        $expense,
        'expenses'
    );

    return ApiResponse::success(
        new AttachmentResource($attachment),
        'Attachment uploaded successfully',
        201
    );
}
```

### 4.6 Deep Linking

**Purpose**: Allow users to open specific screens from notifications or shared links.

**Setup**:

```javascript
// app.json (Expo config)
{
  "expo": {
    "scheme": "expensesettle",
    "ios": {
      "associatedDomains": ["applinks:expensesettle.com"]
    },
    "android": {
      "intentFilters": [
        {
          "action": "VIEW",
          "data": [
            {
              "scheme": "https",
              "host": "expensesettle.com",
              "pathPrefix": "/app"
            }
          ],
          "category": ["BROWSABLE", "DEFAULT"]
        }
      ]
    }
  }
}
```

**React Native Navigation**:

```javascript
import * as Linking from 'expo-linking';
import { useEffect } from 'react';

function App() {
  useEffect(() => {
    // Handle deep links when app is already open
    const subscription = Linking.addEventListener('url', handleDeepLink);

    // Handle deep link when app opens from closed state
    Linking.getInitialURL().then(url => {
      if (url) handleDeepLink({ url });
    });

    return () => subscription.remove();
  }, []);

  const handleDeepLink = ({ url }) => {
    const { path, queryParams } = Linking.parse(url);

    // expensesettle://expense/123
    // https://expensesettle.com/app/expense/123

    if (path === 'expense' && queryParams.id) {
      navigation.navigate('ExpenseDetail', { id: queryParams.id });
    } else if (path === 'group' && queryParams.id) {
      navigation.navigate('GroupDetail', { id: queryParams.id });
    }
  };

  return <NavigationContainer linking={linkingConfig}>...</NavigationContainer>;
}
```

**Backend - Generate Deep Links**:

```php
// In notifications or emails
$deepLink = "expensesettle://expense/{$expense->id}";
$webFallback = "https://expensesettle.com/app/expense/{$expense->id}";
```

---

## 5. Recommended Implementation: React Native + Expo

Based on your application's requirements and current tech stack, **React Native with Expo** is the optimal choice.

### 5.1 Why React Native + Expo?

1. **Familiarity**: You're using Vite (JavaScript ecosystem), making React Native a natural transition
2. **Speed**: Fastest time to market (2.5-3 months)
3. **Cost-Effective**: Single codebase for both platforms
4. **Expo Benefits**: Camera, notifications, and biometrics built-in
5. **Community**: Largest support network
6. **Future-Proof**: Meta (Facebook) actively maintains it

### 5.2 Project Structure

```
expensesettle-mobile/
├── app/                           # Expo Router (file-based routing)
│   ├── (auth)/
│   │   ├── login.tsx
│   │   └── register.tsx
│   ├── (tabs)/
│   │   ├── _layout.tsx
│   │   ├── index.tsx             # Dashboard
│   │   ├── groups.tsx
│   │   └── profile.tsx
│   ├── group/
│   │   ├── [id].tsx              # Group detail
│   │   └── [id]/expenses/
│   │       └── [expenseId].tsx   # Expense detail
│   └── _layout.tsx
├── components/
│   ├── ui/
│   │   ├── Button.tsx
│   │   ├── Input.tsx
│   │   └── Card.tsx
│   ├── expense/
│   │   ├── ExpenseCard.tsx
│   │   ├── ExpenseForm.tsx
│   │   └── ExpenseSplitForm.tsx
│   └── group/
│       ├── GroupCard.tsx
│       └── MemberList.tsx
├── services/
│   ├── api/
│   │   ├── client.ts             # Axios instance
│   │   ├── auth.ts
│   │   ├── groups.ts
│   │   ├── expenses.ts
│   │   └── payments.ts
│   ├── storage/
│   │   └── SecureStorage.ts
│   └── offline/
│       └── OfflineManager.ts
├── store/                         # State management
│   ├── authStore.ts              # Zustand store
│   ├── groupStore.ts
│   └── expenseStore.ts
├── hooks/
│   ├── useAuth.ts
│   ├── useGroups.ts
│   ├── useExpenses.ts
│   └── useOffline.ts
├── types/
│   ├── auth.ts
│   ├── group.ts
│   ├── expense.ts
│   └── api.ts
├── utils/
│   ├── validation.ts
│   ├── formatting.ts
│   └── constants.ts
├── app.json                       # Expo config
├── package.json
└── tsconfig.json
```

### 5.3 Step-by-Step Implementation

#### Phase 1: Project Setup (Week 1)

**Step 1.1: Initialize Expo Project**

```bash
# Install Expo CLI globally
npm install -g expo-cli

# Create new Expo project with TypeScript
npx create-expo-app expensesettle-mobile --template expo-template-blank-typescript

cd expensesettle-mobile

# Install essential dependencies
npx expo install expo-router expo-linking expo-constants expo-status-bar
npx expo install @react-navigation/native @react-navigation/native-stack
npx expo install react-native-safe-area-context react-native-screens
```

**Step 1.2: Install Core Libraries**

```bash
# HTTP client & state management
npm install axios zustand
npm install @tanstack/react-query

# Secure storage
npx expo install expo-secure-store

# UI components
npm install react-native-paper
npm install react-native-vector-icons

# Forms & validation
npm install react-hook-form zod

# Notifications
npx expo install expo-notifications expo-device

# Camera & images
npx expo install expo-image-picker expo-image-manipulator

# Biometrics
npx expo install expo-local-authentication

# Network status
npm install @react-native-community/netinfo

# Date utilities
npm install date-fns

# TypeScript types
npm install -D @types/react @types/react-native
```

**Step 1.3: Configure TypeScript**

```json
// tsconfig.json
{
  "extends": "expo/tsconfig.base",
  "compilerOptions": {
    "strict": true,
    "paths": {
      "@/*": ["./src/*"],
      "@components/*": ["./components/*"],
      "@services/*": ["./services/*"],
      "@hooks/*": ["./hooks/*"],
      "@store/*": ["./store/*"],
      "@types/*": ["./types/*"],
      "@utils/*": ["./utils/*"]
    }
  }
}
```

**Step 1.4: Configure Expo**

```json
// app.json
{
  "expo": {
    "name": "ExpenseSettle",
    "slug": "expensesettle-mobile",
    "version": "1.0.0",
    "orientation": "portrait",
    "icon": "./assets/icon.png",
    "userInterfaceStyle": "automatic",
    "scheme": "expensesettle",
    "splash": {
      "image": "./assets/splash.png",
      "resizeMode": "contain",
      "backgroundColor": "#ffffff"
    },
    "assetBundlePatterns": ["**/*"],
    "ios": {
      "supportsTablet": true,
      "bundleIdentifier": "com.expensesettle.app",
      "infoPlist": {
        "NSCameraUsageDescription": "ExpenseSettle needs camera access to scan receipts",
        "NSPhotoLibraryUsageDescription": "ExpenseSettle needs photo library access to attach receipts",
        "NSFaceIDUsageDescription": "ExpenseSettle uses Face ID for quick login"
      }
    },
    "android": {
      "adaptiveIcon": {
        "foregroundImage": "./assets/adaptive-icon.png",
        "backgroundColor": "#ffffff"
      },
      "package": "com.expensesettle.app",
      "permissions": [
        "CAMERA",
        "READ_EXTERNAL_STORAGE",
        "WRITE_EXTERNAL_STORAGE",
        "USE_BIOMETRIC",
        "USE_FINGERPRINT"
      ]
    },
    "web": {
      "favicon": "./assets/favicon.png"
    },
    "plugins": [
      "expo-router",
      [
        "expo-local-authentication",
        {
          "faceIDPermission": "Allow ExpenseSettle to use Face ID for quick login"
        }
      ]
    ],
    "extra": {
      "apiUrl": "https://api.expensesettle.com/api/v1",
      "eas": {
        "projectId": "your-project-id"
      }
    }
  }
}
```

#### Phase 2: API Integration (Week 2)

**Step 2.1: Create API Client**

```typescript
// services/api/client.ts
import axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';

const API_URL = Constants.expoConfig?.extra?.apiUrl || 'http://localhost:8000/api/v1';

class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: API_URL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Request interceptor - attach auth token
    this.client.interceptors.request.use(
      async (config) => {
        const token = await SecureStore.getItemAsync('access_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor - handle errors
    this.client.interceptors.response.use(
      (response) => response,
      async (error) => {
        if (error.response?.status === 401) {
          // Token expired - logout user
          await SecureStore.deleteItemAsync('access_token');
          // Trigger logout in your app (emit event or use store)
        }
        return Promise.reject(error);
      }
    );
  }

  async get<T>(url: string, config?: AxiosRequestConfig) {
    const response = await this.client.get<T>(url, config);
    return response.data;
  }

  async post<T>(url: string, data?: any, config?: AxiosRequestConfig) {
    const response = await this.client.post<T>(url, data, config);
    return response.data;
  }

  async put<T>(url: string, data?: any, config?: AxiosRequestConfig) {
    const response = await this.client.put<T>(url, data, config);
    return response.data;
  }

  async delete<T>(url: string, config?: AxiosRequestConfig) {
    const response = await this.client.delete<T>(url, config);
    return response.data;
  }
}

export const apiClient = new ApiClient();
```

**Step 2.2: Create Type Definitions**

```typescript
// types/api.ts
export interface ApiResponse<T> {
  status: 'success' | 'error';
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}

// types/auth.ts
export interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface LoginRequest {
  pin: string;
  device_name?: string;
}

export interface LoginResponse {
  user: User;
  access_token: string;
  token_type: string;
}

export interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  pin: string;
  pin_confirmation: string;
  device_name?: string;
}

// types/group.ts
export interface Group {
  id: number;
  name: string;
  description: string | null;
  icon: string;
  created_by: number;
  member_count: number;
  total_expenses: number;
  created_at: string;
  updated_at: string;
}

// types/expense.ts
export interface Expense {
  id: number;
  group_id: number;
  payer_id: number;
  title: string;
  description: string | null;
  amount: number;
  date: string;
  status: 'pending' | 'partially_paid' | 'fully_paid';
  split_type: 'equal' | 'custom';
  payer?: User;
  splits?: ExpenseSplit[];
  created_at: string;
  updated_at: string;
}

export interface ExpenseSplit {
  id: number;
  expense_id: number;
  user_id: number;
  share_amount: number;
  paid_amount: number;
  status: 'pending' | 'paid';
  user?: User;
}
```

**Step 2.3: Create API Service Modules**

```typescript
// services/api/auth.ts
import { apiClient } from './client';
import type { ApiResponse, LoginRequest, LoginResponse, RegisterRequest } from '@types/api';

export const authApi = {
  login: (credentials: LoginRequest) =>
    apiClient.post<ApiResponse<LoginResponse>>('/auth/login', credentials),

  register: (data: RegisterRequest) =>
    apiClient.post<ApiResponse<LoginResponse>>('/auth/register', data),

  logout: () =>
    apiClient.post<ApiResponse<null>>('/auth/logout'),

  me: () =>
    apiClient.get<ApiResponse<{ user: User }>>('/auth/me'),

  refresh: () =>
    apiClient.post<ApiResponse<{ access_token: string }>>('/auth/refresh'),
};
```

```typescript
// services/api/groups.ts
import { apiClient } from './client';
import type { ApiResponse, Group } from '@types/api';

export const groupsApi = {
  getAll: () =>
    apiClient.get<ApiResponse<Group[]>>('/groups'),

  getById: (id: number) =>
    apiClient.get<ApiResponse<Group>>(`/groups/${id}`),

  create: (data: Partial<Group>) =>
    apiClient.post<ApiResponse<Group>>('/groups', data),

  update: (id: number, data: Partial<Group>) =>
    apiClient.put<ApiResponse<Group>>(`/groups/${id}`, data),

  delete: (id: number) =>
    apiClient.delete<ApiResponse<null>>(`/groups/${id}`),

  addMember: (groupId: number, userId: number) =>
    apiClient.post<ApiResponse<null>>(`/groups/${groupId}/members`, { user_id: userId }),

  removeMember: (groupId: number, memberId: number) =>
    apiClient.delete<ApiResponse<null>>(`/groups/${groupId}/members/${memberId}`),

  leaveGroup: (groupId: number) =>
    apiClient.post<ApiResponse<null>>(`/groups/${groupId}/leave`),
};
```

```typescript
// services/api/expenses.ts
import { apiClient } from './client';
import type { ApiResponse, Expense } from '@types/api';

export const expensesApi = {
  getAll: (groupId: number) =>
    apiClient.get<ApiResponse<Expense[]>>(`/groups/${groupId}/expenses`),

  getById: (groupId: number, expenseId: number) =>
    apiClient.get<ApiResponse<Expense>>(`/groups/${groupId}/expenses/${expenseId}`),

  create: (groupId: number, data: Partial<Expense>) =>
    apiClient.post<ApiResponse<Expense>>(`/groups/${groupId}/expenses`, data),

  update: (groupId: number, expenseId: number, data: Partial<Expense>) =>
    apiClient.put<ApiResponse<Expense>>(`/groups/${groupId}/expenses/${expenseId}`, data),

  delete: (groupId: number, expenseId: number) =>
    apiClient.delete<ApiResponse<null>>(`/groups/${groupId}/expenses/${expenseId}`),
};
```

#### Phase 3: State Management (Week 2)

**Step 3.1: Create Auth Store**

```typescript
// store/authStore.ts
import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import { authApi } from '@services/api/auth';
import type { User, LoginRequest } from '@types/api';

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  login: (credentials: LoginRequest) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
  loadUser: () => Promise<void>;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  login: async (credentials) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authApi.login(credentials);

      if (response.status === 'success' && response.data) {
        await SecureStore.setItemAsync('access_token', response.data.access_token);
        set({
          user: response.data.user,
          isAuthenticated: true,
          isLoading: false,
        });
      }
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Login failed',
        isLoading: false,
      });
      throw error;
    }
  },

  register: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authApi.register(data);

      if (response.status === 'success' && response.data) {
        await SecureStore.setItemAsync('access_token', response.data.access_token);
        set({
          user: response.data.user,
          isAuthenticated: true,
          isLoading: false,
        });
      }
    } catch (error: any) {
      set({
        error: error.response?.data?.message || 'Registration failed',
        isLoading: false,
      });
      throw error;
    }
  },

  logout: async () => {
    try {
      await authApi.logout();
    } catch (error) {
      // Continue with logout even if API call fails
    } finally {
      await SecureStore.deleteItemAsync('access_token');
      set({
        user: null,
        isAuthenticated: false,
        error: null,
      });
    }
  },

  loadUser: async () => {
    const token = await SecureStore.getItemAsync('access_token');

    if (!token) {
      set({ isAuthenticated: false, isLoading: false });
      return;
    }

    set({ isLoading: true });
    try {
      const response = await authApi.me();

      if (response.status === 'success' && response.data) {
        set({
          user: response.data.user,
          isAuthenticated: true,
          isLoading: false,
        });
      }
    } catch (error) {
      await SecureStore.deleteItemAsync('access_token');
      set({
        user: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  },

  clearError: () => set({ error: null }),
}));
```

#### Phase 4: UI Components (Weeks 3-4)

**Step 4.1: Login Screen**

```typescript
// app/(auth)/login.tsx
import React, { useState } from 'react';
import { View, StyleSheet, Alert } from 'react-native';
import { TextInput, Button, Text } from 'react-native-paper';
import { useAuthStore } from '@store/authStore';
import { useRouter } from 'expo-router';
import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

export default function LoginScreen() {
  const [pin, setPin] = useState('');
  const [loading, setLoading] = useState(false);
  const { login, error } = useAuthStore();
  const router = useRouter();

  const handleLogin = async () => {
    if (pin.length !== 6) {
      Alert.alert('Invalid PIN', 'Please enter a 6-digit PIN');
      return;
    }

    setLoading(true);
    try {
      await login({ pin });

      // Save PIN for biometric login
      await SecureStore.setItemAsync('user_pin', pin);

      router.replace('/(tabs)');
    } catch (error) {
      Alert.alert('Login Failed', 'Invalid PIN. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const handleBiometricLogin = async () => {
    const hasHardware = await LocalAuthentication.hasHardwareAsync();
    const isEnrolled = await LocalAuthentication.isEnrolledAsync();

    if (!hasHardware || !isEnrolled) {
      Alert.alert('Biometrics Unavailable', 'Please use PIN to login');
      return;
    }

    const savedPin = await SecureStore.getItemAsync('user_pin');

    if (!savedPin) {
      Alert.alert('No Saved Credentials', 'Please login with PIN first');
      return;
    }

    const result = await LocalAuthentication.authenticateAsync({
      promptMessage: 'Login to ExpenseSettle',
      fallbackLabel: 'Use PIN',
    });

    if (result.success) {
      setLoading(true);
      try {
        await login({ pin: savedPin });
        router.replace('/(tabs)');
      } catch (error) {
        Alert.alert('Login Failed', 'Please try again');
      } finally {
        setLoading(false);
      }
    }
  };

  return (
    <View style={styles.container}>
      <Text variant="headlineLarge" style={styles.title}>
        ExpenseSettle
      </Text>

      <TextInput
        label="6-Digit PIN"
        value={pin}
        onChangeText={setPin}
        keyboardType="numeric"
        maxLength={6}
        secureTextEntry
        style={styles.input}
      />

      {error && <Text style={styles.error}>{error}</Text>}

      <Button
        mode="contained"
        onPress={handleLogin}
        loading={loading}
        disabled={loading || pin.length !== 6}
        style={styles.button}
      >
        Login
      </Button>

      <Button
        mode="outlined"
        onPress={handleBiometricLogin}
        style={styles.button}
      >
        Use Biometrics
      </Button>

      <Button
        mode="text"
        onPress={() => router.push('/(auth)/register')}
      >
        Don't have an account? Register
      </Button>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  title: {
    textAlign: 'center',
    marginBottom: 40,
  },
  input: {
    marginBottom: 16,
  },
  button: {
    marginVertical: 8,
  },
  error: {
    color: 'red',
    marginBottom: 8,
  },
});
```

**Step 4.2: Dashboard Screen**

```typescript
// app/(tabs)/index.tsx
import React, { useEffect } from 'react';
import { View, FlatList, StyleSheet, RefreshControl } from 'react-native';
import { Card, Text, FAB } from 'react-native-paper';
import { useRouter } from 'expo-router';
import { useQuery } from '@tanstack/react-query';
import { groupsApi } from '@services/api/groups';

export default function DashboardScreen() {
  const router = useRouter();

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['groups'],
    queryFn: async () => {
      const response = await groupsApi.getAll();
      return response.data || [];
    },
  });

  return (
    <View style={styles.container}>
      <FlatList
        data={data}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <Card
            style={styles.card}
            onPress={() => router.push(`/group/${item.id}`)}
          >
            <Card.Content>
              <Text variant="titleLarge">{item.icon} {item.name}</Text>
              <Text variant="bodyMedium">{item.member_count} members</Text>
              <Text variant="bodySmall">
                Total expenses: ${item.total_expenses.toFixed(2)}
              </Text>
            </Card.Content>
          </Card>
        )}
        refreshControl={
          <RefreshControl refreshing={isLoading} onRefresh={refetch} />
        }
        ListEmptyComponent={
          <Text style={styles.emptyText}>No groups yet. Create one!</Text>
        }
      />

      <FAB
        icon="plus"
        style={styles.fab}
        onPress={() => router.push('/group/create')}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
  },
  card: {
    marginBottom: 12,
  },
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
  },
  emptyText: {
    textAlign: 'center',
    marginTop: 40,
  },
});
```

#### Phase 5: Advanced Features (Weeks 5-8)

Continue implementing:
- Expense creation with receipt scanning
- Payment tracking
- Offline sync
- Push notifications
- Group management
- Split calculations

#### Phase 6: Testing & Refinement (Weeks 9-10)

- Unit tests with Jest
- Integration tests
- E2E testing with Detox
- Performance optimization
- Bug fixes
- Beta testing with TestFlight/Google Play Internal Testing

#### Phase 7: Deployment (Weeks 11-12)

- Build production apps with EAS Build
- App Store submission (iOS)
- Google Play submission (Android)
- Backend deployment to production
- Monitoring setup

---

## 6. Timeline and Effort Estimation

### Detailed Timeline (React Native + Expo)

| Week | Phase | Tasks | Deliverables |
|------|-------|-------|--------------|
| 1 | Project Setup | - Initialize Expo project<br>- Install dependencies<br>- Configure TypeScript<br>- Setup folder structure | Working skeleton app |
| 2 | API Integration | - Create API client<br>- Implement authentication<br>- Create type definitions<br>- Setup state management | Functional API layer |
| 3-4 | Core UI | - Login/Register screens<br>- Dashboard<br>- Group list/detail<br>- Basic navigation | Core user flows working |
| 5-6 | Expense Features | - Expense creation form<br>- Receipt scanning<br>- Split calculation<br>- Expense detail view | Complete expense management |
| 7 | Payments | - Payment tracking<br>- Settlement calculation<br>- Payment history | Payment features complete |
| 8 | Advanced Features | - Push notifications<br>- Biometric auth<br>- Deep linking<br>- Offline sync | All features implemented |
| 9-10 | Testing | - Unit tests<br>- Integration tests<br>- Bug fixes<br>- Performance optimization | Production-ready app |
| 11-12 | Deployment | - App Store submission<br>- Google Play submission<br>- Production backend<br>- Launch preparation | Apps in stores |

### Resource Allocation

**Team Composition** (Recommended):
- 1 Full-stack Developer (React Native + Laravel): 100% for 12 weeks
- OR
- 1 Mobile Developer (React Native): 80% for 12 weeks
- 1 Backend Developer (Laravel): 40% for 4 weeks (API modifications)

**Budget Estimation**:
- Developer cost: $15,000 - $25,000 (freelance) or $25,000 - $40,000 (agency)
- App Store fees: $99/year (iOS) + $25 one-time (Android)
- Backend infrastructure: $50-200/month
- Third-party services (OCR, push notifications): $0-100/month
- Total initial investment: $16,000 - $42,000

---

## 7. Tools and Libraries

### Backend (Laravel)

| Category | Tool/Library | Purpose |
|----------|-------------|---------|
| Authentication | Laravel Sanctum | JWT token management |
| API Resources | Laravel API Resources | JSON response formatting |
| File Storage | Laravel Storage + AWS S3 | Receipt/attachment storage |
| OCR Processing | Google Cloud Vision API | Receipt text extraction |
| Push Notifications | Laravel FCM Channel | Firebase push notifications |
| Queue Management | Laravel Queues + Redis | Background job processing |
| Rate Limiting | Laravel RateLimiter | API throttling |
| Testing | PHPUnit / Pest | Backend testing |

### Mobile (React Native + Expo)

| Category | Tool/Library | Purpose |
|----------|-------------|---------|
| Framework | React Native + Expo | Core framework |
| Navigation | Expo Router | File-based routing |
| State Management | Zustand | Global state |
| Server State | TanStack Query | API data caching |
| HTTP Client | Axios | API requests |
| Forms | React Hook Form | Form management |
| Validation | Zod | Schema validation |
| UI Components | React Native Paper | Material Design components |
| Secure Storage | Expo SecureStore | Token/PIN storage |
| Biometrics | Expo Local Authentication | Fingerprint/Face ID |
| Camera | Expo Image Picker | Receipt capture |
| Notifications | Expo Notifications | Push notifications |
| Offline Storage | AsyncStorage | Local data cache |
| Network Status | NetInfo | Online/offline detection |
| Date Utilities | date-fns | Date formatting |
| Testing | Jest + React Native Testing Library | Unit/integration tests |
| E2E Testing | Detox | End-to-end testing |

### Development Tools

| Tool | Purpose |
|------|---------|
| Expo Go | Development testing on physical devices |
| EAS Build | Production app builds |
| EAS Submit | App store submission |
| Flipper | React Native debugging |
| Reactotron | State debugging |
| Postman | API testing |
| TablePlus | Database management |

---

## 8. Common Pitfalls to Avoid

### 8.1 Architecture & Design Pitfalls

#### Pitfall 1: Not Planning for Offline Mode from the Start
**Problem**: Attempting to add offline functionality after building the app leads to major refactoring.

**Solution**:
- Design data models with local-first approach
- Implement optimistic UI updates from day 1
- Use conflict resolution strategies (last-write-wins, CRDTs)
- Always store timestamps for sync conflict detection

```typescript
// Good: Optimistic update pattern
const createExpense = async (expenseData) => {
  const tempId = `temp_${Date.now()}`;
  const optimisticExpense = { ...expenseData, id: tempId, status: 'syncing' };

  // Update UI immediately
  addExpenseToCache(optimisticExpense);

  try {
    const response = await api.post('/expenses', expenseData);
    // Replace temp with real data
    replaceExpenseInCache(tempId, response.data);
  } catch (error) {
    // Revert on failure
    removeExpenseFromCache(tempId);
    showError('Failed to create expense');
  }
};
```

#### Pitfall 2: Inconsistent Error Handling
**Problem**: Different error formats from backend cause app crashes.

**Solution**:
- Standardize all API error responses
- Create centralized error handling utility
- Always validate response structure

```typescript
// Good: Centralized error handler
class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>
  ) {
    super(message);
  }
}

const handleApiError = (error: any): ApiError => {
  if (error.response?.data) {
    return new ApiError(
      error.response.data.message || 'An error occurred',
      error.response.status,
      error.response.data.errors
    );
  }
  return new ApiError('Network error', 0);
};
```

#### Pitfall 3: Over-fetching Data
**Problem**: Loading entire group data when only summary is needed wastes bandwidth.

**Solution**:
- Use pagination for lists
- Implement lazy loading for relationships
- Create specific endpoints for summaries

```php
// Bad: Loading everything
Route::get('/groups/{group}', function ($group) {
    return Group::with('members.user', 'expenses.splits', 'expenses.attachments')
        ->findOrFail($group);
});

// Good: Selective loading
Route::get('/groups/{group}/summary', function ($group) {
    return Group::select('id', 'name', 'icon')
        ->withCount('members', 'expenses')
        ->withSum('expenses', 'amount')
        ->findOrFail($group);
});
```

### 8.2 Authentication & Security Pitfalls

#### Pitfall 4: Storing Sensitive Data Insecurely
**Problem**: Storing tokens in AsyncStorage (unencrypted) exposes them to attacks.

**Solution**:
- Always use SecureStore for tokens and PINs
- Never log sensitive information
- Implement certificate pinning for production

```typescript
// Bad
import AsyncStorage from '@react-native-async-storage/async-storage';
await AsyncStorage.setItem('token', token); // Unencrypted!

// Good
import * as SecureStore from 'expo-secure-store';
await SecureStore.setItemAsync('access_token', token); // Encrypted
```

#### Pitfall 5: Not Implementing Token Refresh
**Problem**: Tokens expire, forcing user to login repeatedly.

**Solution**:
- Implement automatic token refresh
- Handle 401 errors gracefully
- Refresh before expiration, not after

```typescript
// Good: Auto-refresh interceptor
axios.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const newToken = await authApi.refresh();
        await SecureStore.setItemAsync('access_token', newToken);
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return axios(originalRequest);
      } catch (refreshError) {
        // Logout user
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);
```

#### Pitfall 6: Weak Rate Limiting
**Problem**: No rate limiting allows brute force attacks on PIN login.

**Solution**:
- Implement exponential backoff
- Track attempts per IP and per device
- Add CAPTCHA after multiple failures

### 8.3 Performance Pitfalls

#### Pitfall 7: Not Memoizing Components
**Problem**: Unnecessary re-renders slow down the app.

**Solution**:
- Use React.memo for expensive components
- Implement useMemo for expensive calculations
- Use useCallback for callback props

```typescript
// Bad
const ExpenseList = ({ expenses, onPress }) => (
  <FlatList
    data={expenses}
    renderItem={({ item }) => (
      <ExpenseCard expense={item} onPress={() => onPress(item.id)} />
    )}
  />
);

// Good
const ExpenseCard = React.memo(({ expense, onPress }) => (
  <Card onPress={onPress}>...</Card>
));

const ExpenseList = ({ expenses, onPress }) => {
  const renderItem = useCallback(
    ({ item }) => (
      <ExpenseCard
        expense={item}
        onPress={() => onPress(item.id)}
      />
    ),
    [onPress]
  );

  return <FlatList data={expenses} renderItem={renderItem} />;
};
```

#### Pitfall 8: Large Image Uploads
**Problem**: Uploading full-resolution photos from camera crashes the app or takes too long.

**Solution**:
- Always compress images before upload
- Resize to reasonable dimensions (1024x1024 max)
- Show upload progress

```typescript
// Good: Image compression before upload
import * as ImageManipulator from 'expo-image-manipulator';

const compressImage = async (uri: string) => {
  const result = await ImageManipulator.manipulateAsync(
    uri,
    [{ resize: { width: 1024 } }],
    { compress: 0.7, format: ImageManipulator.SaveFormat.JPEG }
  );
  return result.uri;
};
```

#### Pitfall 9: Not Implementing Pagination
**Problem**: Loading all expenses at once for a group with 1000+ expenses freezes the app.

**Solution**:
- Always paginate lists
- Implement infinite scroll
- Cache pages locally

```php
// Backend
Route::get('/groups/{group}/expenses', function ($group) {
    return ExpenseResource::collection(
        Expense::where('group_id', $group)
            ->latest()
            ->paginate(20)
    );
});
```

```typescript
// Mobile
const { data, fetchNextPage, hasNextPage } = useInfiniteQuery({
  queryKey: ['expenses', groupId],
  queryFn: ({ pageParam = 1 }) =>
    expensesApi.getAll(groupId, { page: pageParam }),
  getNextPageParam: (lastPage) => lastPage.pagination.next_page,
});
```

### 8.4 State Management Pitfalls

#### Pitfall 10: Mixing Local and Server State
**Problem**: Managing API data in Zustand causes stale data and sync issues.

**Solution**:
- Use Zustand only for UI state (theme, modals, etc.)
- Use TanStack Query for server state
- Clear separation of concerns

```typescript
// Bad: Mixing concerns
const useStore = create((set) => ({
  user: null,
  expenses: [],
  modalOpen: false,
  fetchExpenses: async () => {
    const data = await api.get('/expenses');
    set({ expenses: data });
  },
}));

// Good: Separation
// UI state only
const useUIStore = create((set) => ({
  modalOpen: false,
  theme: 'light',
  toggleModal: () => set(state => ({ modalOpen: !state.modalOpen })),
}));

// Server state with React Query
const useExpenses = (groupId) => useQuery({
  queryKey: ['expenses', groupId],
  queryFn: () => expensesApi.getAll(groupId),
});
```

### 8.5 Mobile-Specific Pitfalls

#### Pitfall 11: Not Handling Keyboard Properly
**Problem**: Keyboard covers input fields, poor UX.

**Solution**:
- Use KeyboardAvoidingView
- Implement ScrollView with keyboardShouldPersistTaps
- Auto-scroll to focused input

```typescript
// Good
import { KeyboardAvoidingView, Platform } from 'react-native';

<KeyboardAvoidingView
  behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
  style={{ flex: 1 }}
>
  <ScrollView keyboardShouldPersistTaps="handled">
    <TextInput ... />
  </ScrollView>
</KeyboardAvoidingView>
```

#### Pitfall 12: Not Testing on Real Devices
**Problem**: App works in simulator but crashes on real devices.

**Solution**:
- Test on multiple device sizes (iPhone SE, iPhone 14 Pro Max, various Android)
- Test on older devices (iOS 13, Android 10)
- Use Expo Go for quick physical device testing
- Test with poor network conditions

#### Pitfall 13: Ignoring Platform Differences
**Problem**: Assuming iOS and Android behave identically.

**Solution**:
- Use Platform.select() for platform-specific code
- Test navigation on both platforms
- Handle different permission flows

```typescript
// Good: Platform-specific styling
import { Platform, StyleSheet } from 'react-native';

const styles = StyleSheet.create({
  container: {
    paddingTop: Platform.select({
      ios: 20,
      android: 25,
    }),
    ...Platform.select({
      ios: {
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 3.84,
      },
      android: {
        elevation: 5,
      },
    }),
  },
});
```

### 8.6 Deployment Pitfalls

#### Pitfall 14: Not Using Environment Variables
**Problem**: Hardcoding API URLs prevents switching between dev/staging/prod.

**Solution**:
- Use app.json extra config
- Create separate build configurations
- Never commit sensitive keys

```json
// app.json
{
  "expo": {
    "extra": {
      "apiUrl": process.env.API_URL || "https://api.expensesettle.com/api/v1",
      "googleVisionApiKey": process.env.GOOGLE_VISION_KEY,
    }
  }
}
```

#### Pitfall 15: Ignoring App Size
**Problem**: App exceeds 150MB, users won't download over cellular.

**Solution**:
- Enable Hermes for smaller bundle size
- Use on-demand resources
- Lazy load large dependencies
- Optimize images with CDN

```json
// app.json - Enable Hermes
{
  "expo": {
    "android": {
      "enableHermes": true
    },
    "ios": {
      "enableHermes": true
    }
  }
}
```

#### Pitfall 16: No Crash Reporting
**Problem**: Users experience crashes but you have no visibility.

**Solution**:
- Integrate Sentry or Bugsnag from day 1
- Log errors with context
- Monitor performance metrics

```typescript
import * as Sentry from '@sentry/react-native';

Sentry.init({
  dsn: 'your-sentry-dsn',
  enableInExpoDevelopment: false,
  debug: __DEV__,
});

// Wrap root component
export default Sentry.wrap(App);
```

### 8.7 Laravel API Pitfalls

#### Pitfall 17: N+1 Query Problems
**Problem**: Loading 100 expenses triggers 100 additional queries for payer data.

**Solution**:
- Always use eager loading
- Monitor queries with Laravel Debugbar
- Use lazy eager loading when appropriate

```php
// Bad
$expenses = Expense::all();
foreach ($expenses as $expense) {
    echo $expense->payer->name; // N+1 query
}

// Good
$expenses = Expense::with('payer')->get();
foreach ($expenses as $expense) {
    echo $expense->payer->name; // Single query
}
```

#### Pitfall 18: Not Validating Mobile Requests Properly
**Problem**: Assuming mobile requests are identical to web requests.

**Solution**:
- Create separate Form Requests for API
- Validate file sizes (mobile photos can be huge)
- Return proper JSON validation errors

#### Pitfall 19: CORS Misconfiguration
**Problem**: Mobile app can't make requests due to CORS errors.

**Solution**:
- Properly configure Laravel CORS
- Allow necessary headers (Authorization, Content-Type)
- Test with actual mobile app, not just Postman

---

## Conclusion

Converting your ExpenseSettle Laravel web application to mobile apps is a substantial but achievable project. The recommended approach is **React Native with Expo**, which offers the best balance of:

- Development speed (2.5-3 months)
- Code reuse (95% between iOS and Android)
- Developer availability
- Cost-effectiveness
- Future maintainability

### Next Steps

1. **Week 1**: Set up development environment and initialize Expo project
2. **Week 2**: Transform Laravel routes to API endpoints and implement JWT authentication
3. **Weeks 3-8**: Build mobile app features incrementally
4. **Weeks 9-10**: Test thoroughly on multiple devices
5. **Weeks 11-12**: Deploy to App Store and Google Play

### Success Metrics

Track these KPIs to measure success:
- App startup time < 3 seconds
- API response time < 500ms
- Crash rate < 1%
- User retention > 40% (day 7)
- App Store rating > 4.0 stars

### Support & Maintenance

Post-launch plan:
- Monitor crash reports daily
- Release updates every 2-4 weeks
- Respond to user feedback within 48 hours
- Keep dependencies updated monthly
- Plan for quarterly feature releases

This guide provides a comprehensive roadmap for your mobile app development journey. Good luck with the conversion!

---

**Document Version**: 1.0
**Last Updated**: December 7, 2025
**Author**: Technical Architecture Team
**For Project**: ExpenseSettle Mobile Conversion
