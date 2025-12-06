# Laravel Configuration for Mobile App

Since the Capacitor app will load your web app in a WebView (essentially an in-app browser), your Laravel backend needs minimal changes. However, a few configurations ensure smooth mobile experience.

---

## 1. Configure CORS (For API Calls from Mobile)

If you want to make API calls from the mobile app to your Laravel backend, update:

File: `config/cors.php`

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

Or in `.env`:

```env
# Allow mobile app to make API requests
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_METHODS=*
CORS_ALLOWED_HEADERS=*
```

---

## 2. Update Session Configuration for Mobile

File: `config/session.php`

```php
// For mobile app webview, use database sessions
'driver' => env('SESSION_DRIVER', 'database'),

// Allow same-site cookies from mobile context
'same_site' => 'lax',

// Secure flag for HTTPS (recommended for production)
'secure' => env('SESSION_SECURE_COOKIES', false),

// HttpOnly to prevent JS access
'http_only' => true,
```

---

## 3. Add Device Identification Header

Mobile app will send a custom header to identify itself. Update your middleware:

File: `app/Http/Middleware/HandleMobileRequests.php` (create new)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleMobileRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Detect if request is from mobile app
        $userAgent = $request->header('User-Agent', '');

        // Capacitor apps have this user agent
        if (strpos($userAgent, 'ExpenseSettle') !== false ||
            strpos($userAgent, 'Capacitor') !== false) {
            $request->attributes->set('is_mobile_app', true);
        }

        // Allow mobile app to bypass CSRF protection if needed
        if ($request->attributes->get('is_mobile_app')) {
            // Capacitor can access CSRF token from meta tag
            // No additional changes needed
        }

        return $next($request);
    }
}
```

Register middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    // ... existing middleware ...
    $middleware->append(\App\Http\Middleware\HandleMobileRequests::class);
})
```

---

## 4. Optimize for Mobile Network

Add this middleware to compress responses for mobile:

File: `app/Http/Middleware/CompressForMobile.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompressForMobile
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Gzip large responses for mobile
        if ($request->attributes->get('is_mobile_app')) {
            $response->header('Content-Encoding', 'gzip');
        }

        return $response;
    }
}
```

---

## 5. Handle Mobile App Deep Linking (Optional)

When user clicks push notification, deep link to specific page:

File: `routes/web.php`

```php
// Deep link routes for mobile app
Route::get('/groups/{group}', function ($group) {
    // Mobile app will capture this route
    // And navigate to appropriate screen
    return view('groups.show', ['group' => $group]);
});

Route::get('/expenses/{expense}', function ($expense) {
    return view('expenses.show', ['expense' => $expense]);
});

Route::get('/groups/{group}/payments', function ($group) {
    return view('groups.payments', ['group' => $group]);
});
```

---

## 6. Configure Camera Permissions (Android)

File: `android/app/src/main/AndroidManifest.xml`

The permissions are already added by Capacitor, but verify:

```xml
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.INTERNET" />
```

---

## 7. Environment Configuration

Create a mobile-specific configuration:

File: `.env.mobile`

```env
# Mobile app will use different server config
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Session configuration
SESSION_DRIVER=database
SESSION_SECURE_COOKIES=true

# Enable mobile-specific logging
LOG_CHANNEL=mobile

# Mobile API rate limiting
RATE_LIMIT_MOBILE=true
```

---

## 8. Add Mobile User Agent Header

Your mobile app will send this header with every request:

```javascript
// In your web app's main JS file
const userAgent = navigator.userAgent;
const isMobileApp = userAgent.includes('Capacitor') || userAgent.includes('ExpenseSettle');

if (isMobileApp) {
    // Set custom header for all requests
    axios.defaults.headers.common['X-Mobile-App'] = 'true';
    axios.defaults.headers.common['X-App-Version'] = '1.0.0';
}
```

---

## 9. Handle Notification Clicks

When mobile app receives push notification, it will deep link:

File: `routes/web.php`

```php
// Notification deep link handler
Route::get('/notification/{type}/{id}', function ($type, $id) {
    // Deep link from push notification
    // Mobile app will navigate here automatically

    switch ($type) {
        case 'group':
            return redirect()->route('groups.show', $id);
        case 'expense':
            return redirect()->route('groups.expenses.show', $id);
        case 'payment':
            return redirect()->route('groups.payments.history', $id);
        default:
            return redirect('/');
    }
});
```

---

## 10. Security Headers for Mobile

File: `app/Http/Middleware/SecurityHeaders.php` (already exists)

Keep these for mobile app:

```php
// Already configured in your middleware
$response->header('X-Frame-Options', 'DENY');
$response->header('X-Content-Type-Options', 'nosniff');
$response->header('X-XSS-Protection', '1; mode=block');
```

For mobile WebView, consider relaxing frame options:

```php
// Allow embedding in mobile app
if ($request->attributes->get('is_mobile_app')) {
    $response->header('X-Frame-Options', 'ALLOW-FROM capacitor://');
}
```

---

## Testing with Mobile App

### Test with iOS Simulator

```bash
# Build and run in iOS simulator
npm run build
npx cap copy
npx cap run ios

# In simulator, your app loads your Laravel server
# Make sure Laravel is running:
php artisan serve --host=0.0.0.0 --port=8000
```

### Test with Android Emulator

```bash
# Build and run in Android emulator
npm run build
npx cap copy
npx cap run android

# In emulator, your app loads your Laravel server
# Make sure Laravel is running
php artisan serve --host=0.0.0.0 --port=8000
```

---

## Debugging Mobile App Requests

### View Logs from Mobile App

```bash
# iOS
npx cap run ios
# Then in Safari > Develop > [Device] > [App] to see console

# Android
# In Android Studio > Logcat
# Filter by "ExpenseSettle" to see app logs
```

### Check Network Requests

```bash
# Monitor network traffic from mobile app
php artisan tail  # See Laravel logs

# Or check browser dev tools
# In iOS: Settings > Safari > Advanced > Web Inspector
```

---

## Deployment Notes

When deploying mobile app to production:

1. **Update `capacitor.config.ts`**:
   ```typescript
   server: {
     url: 'https://yourdomain.com',  // Your production domain
     cleartext: false,  // Only HTTPS in production
   }
   ```

2. **Update `.env` for production**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   SESSION_SECURE_COOKIES=true
   ```

3. **Rebuild web app**:
   ```bash
   npm run build
   npx cap copy
   ```

4. **Rebuild native apps** for iOS and Android

---

## Quick Checklist

- [ ] CORS configured
- [ ] Session driver set to `database`
- [ ] Mobile middleware added
- [ ] Deep linking routes configured
- [ ] Camera permissions added
- [ ] Security headers configured for mobile
- [ ] `.env.mobile` created
- [ ] User Agent detection implemented
- [ ] Laravel server tested with mobile simulator
- [ ] Network requests debugged and verified

---

## Next Steps

1. **Test locally** with iOS simulator/Android emulator
2. **Setup Firebase** for push notifications
3. **Add native features** (camera, biometrics, etc.)
4. **Deploy to App Stores**

---

**All set! Your Laravel app is ready for the mobile wrapper.** 🚀
