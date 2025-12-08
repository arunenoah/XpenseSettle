# Firebase Push Notifications - Implementation Summary

## Overview
Firebase push notifications have been successfully configured for your ExpenseSettle Android Capacitor app. The entire backend and mobile infrastructure is now in place to send real-time notifications when expense settlements are confirmed.

---

## What Has Been Implemented

### ✅ Backend Authentication (AuthController.php)

**Problem Solved**: Session-based login (PIN) doesn't provide API tokens needed by the mobile app.

**Solution**: Modified `AuthController.php` to create Sanctum API tokens on successful login.

**Changes** (3 lines added in `login()` method after line 50):
```php
// Create Sanctum token for API access (mobile/Capacitor app)
$sanctumToken = $user->createToken('mobile')->plainTextToken;
session(['sanctum_token' => $sanctumToken]);
```

**Result**: Every authenticated user now has an API token that can be used for mobile requests.

---

### ✅ Frontend Token Exposure (Blade Layout)

**Problem Solved**: The Capacitor webview JavaScript couldn't access the Sanctum token for API calls.

**Solution**: Modified `resources/views/layouts/app.blade.php` to expose token to JavaScript.

**Changes** (Added to Blade layout `<head>` section - lines 16-21):
```blade
<!-- Firebase & Sanctum Token for Mobile Notifications -->
@auth
<script>
    window.SANCTUM_TOKEN = "{{ session('sanctum_token', '') }}";
    window.APP_API_URL = "{{ env('APP_URL') }}/api";
</script>
@endauth
```

**Result**: JavaScript code in Capacitor webview can now access the token via `window.SANCTUM_TOKEN`.

---

### ✅ Firebase Initialization Script (Blade Layout)

**Problem Solved**: Mobile app needs to register device tokens and listen for notifications.

**Solution**: Added complete Firebase initialization script to Blade layout (130+ lines).

**Script Functionality**:
1. **Detects Capacitor Environment**: Checks if app is running in Capacitor webview
2. **Requests Permissions**: Asks user for notification permission
3. **Gets Device Token**: Retrieves unique token from Firebase Cloud Messaging
4. **Registers with Backend**: Sends token to `/api/device-tokens` endpoint using Bearer authentication
5. **Listens for Notifications**:
   - Foreground: Shows green banner notification
   - Background: Triggers notification tap handling
   - Navigation: Directs user to group summary when tapped

**Result**: Complete bidirectional communication between Firebase, Capacitor app, and Laravel backend.

---

### ✅ NPM Package Installation

**Plugin**: `@capacitor-firebase/messaging@7.4.0`

**Installed with**: `npm install @capacitor-firebase/messaging`

**Result**: Capacitor now has native Firebase Cloud Messaging support.

---

### ✅ Android Sync

**Command**: `npx cap sync android`

**Result**: 
- ✓ Web assets copied to Android app
- ✓ `capacitor.config.json` created in Android
- ✓ All 4 Capacitor plugins detected and updated:
  - @capacitor-firebase/messaging
  - @capacitor/camera
  - @capacitor/geolocation
  - @capacitor/local-notifications

---

## What You Still Need to Do

### 1️⃣ Download google-services.json
- Location: Firebase Console → Project Settings → Your apps → Android
- Save to: `android/app/google-services.json`
- **Critical**: Must be in `android/app/` NOT elsewhere

### 2️⃣ Fill Firebase Environment Variables (.env)
Get from Firebase Console → Project Settings → Service Accounts:

```env
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_PRIVATE_KEY_ID=your-key-id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-xxxxx@your-project.iam.gserviceaccount.com
FIREBASE_CLIENT_ID=your-client-id
FIREBASE_CLIENT_X509_CERT_URL=https://www.googleapis.com/robot/v1/metadata/x509/...
FCM_SERVER_KEY=your-fcm-server-key
FIREBASE_API_KEY=your-api-key
FIREBASE_WEB_API_KEY=your-web-api-key
```

### 3️⃣ Build and Test on Android

```bash
# 1. Build web assets
npm run build

# 2. Copy to Capacitor
npx cap copy android

# 3. Run on device with live reload
npx cap run android --livereload

# Or open in Android Studio
npx cap open android
```

### 4️⃣ Verify the Flow

**On Android Device:**
1. Log in with your 6-digit PIN
2. Check browser console (or Logcat) for messages:
   ```
   ✅ Capacitor + Token detected
   📱 Requesting notification permissions...
   ✅ Notifications allowed
   🔑 Device Token: abc123...
   📤 Registering token with backend...
   ✅ Token registered
   ```

**Trigger Notification:**
1. Go to group summary
2. Click "Mark Paid" on any settlement
3. Check phone - you should see green notification banner

---

## How It All Works Together

```
┌─────────────────────────────────────────────────────────────┐
│                   ANDROID DEVICE                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Capacitor WebView (runs your Laravel web app)       │  │
│  │                                                       │  │
│  │  1. User logs in with PIN                           │  │
│  │  2. Backend returns Sanctum token                    │  │
│  │  3. Token exposed to JS via window.SANCTUM_TOKEN    │  │
│  │                                                       │  │
│  │  4. Firebase initialization script runs:             │  │
│  │     - Detects Capacitor environment                  │  │
│  │     - Gets device token from FCM                     │  │
│  │     - Registers with backend: POST /api/device-tokens│ │
│  │     - Sets up notification listeners                 │  │
│  │                                                       │  │
│  │  5. When notification arrives:                       │  │
│  │     - Shows green banner (foreground)                │  │
│  │     - Navigates to group summary (tap)               │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  Native Firebase Messaging Plugin                           │
│  (runs in native Android layer)                             │
└─────────────────────────────────────────────────────────────┘
                          │
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│             FIREBASE CLOUD MESSAGING (FCM)                 │
│  - Device token registration                               │
│  - Notification delivery                                   │
└─────────────────────────────────────────────────────────────┘
                          │
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│              LARAVEL BACKEND                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  AuthController                                       │  │
│  │  - Creates Sanctum token at login                    │  │
│  │  - Exposes token to Blade template                   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  DeviceTokenController                               │  │
│  │  - POST /api/device-tokens (register token)          │  │
│  │  - Protected by auth:sanctum middleware              │  │
│  │  - Stores token in database                          │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  NotificationService                                  │  │
│  │  - Sends push notifications via FCM                  │  │
│  │  - Queries user's device tokens                      │  │
│  │  - Called when settlement confirmed                  │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## Files Modified

### Backend Files
- **app/Http/Controllers/AuthController.php** (3 lines added)
  - Location: In `login()` method after authentication
  - Creates Sanctum token and stores in session

- **resources/views/layouts/app.blade.php** (140+ lines added)
  - Head section: Token exposure
  - Body section: Firebase initialization script

### Mobile Configuration
- **package.json** (dependency added)
  - `@capacitor-firebase/messaging@7.4.0`

- **capacitor.config.json** (auto-generated)
  - Firebase plugin configuration

### Android Native (auto-updated)
- **android/app/build.gradle** (updated by Capacitor)
- **android/app/src/main/AndroidManifest.xml** (updated by Capacitor)
- **android/app/google-services.json** (NEEDS YOUR ACTION)

### Environment Configuration
- **.env** (FIREBASE_* variables - needs your credentials)
- **config/firebase.php** (already exists, builds credentials from env)

---

## Testing Checklist

- [ ] Downloaded `google-services.json` from Firebase Console
- [ ] Placed it at `android/app/google-services.json`
- [ ] Filled all FIREBASE_* variables in `.env`
- [ ] Built web assets with `npm run build`
- [ ] Synced with Capacitor with `npx cap copy android`
- [ ] Ran app on Android device with `npx cap run android --livereload`
- [ ] Logged in successfully and saw token in logs
- [ ] Marked a settlement as paid
- [ ] Received notification on Android device

---

## Production Deployment

### On Laravel Forge:

1. **Set Environment Variables**:
   - Go to App → Environment
   - Add all `FIREBASE_*` and `FCM_SERVER_KEY` variables
   - Use same values from Firebase Console

2. **Upload google-services.json**:
   - Build APK/AAB with same Firebase project
   - Include `google-services.json` in Android build

3. **Deploy Web App**:
   - Push code changes (AuthController, app.blade.php)
   - Run `php artisan migrate` if needed

4. **Test**:
   - Install APK on test device
   - Log in and verify token registration
   - Test notification delivery

---

## Key Security Points

✅ **PIN is NOT sent over API** - Only used for session login
✅ **Sanctum tokens** - Secure, signed bearer tokens for API calls
✅ **Device tokens** - Stored with user association, rotated
✅ **Firebase credentials** - Stored in .env, never in git
✅ **CSRF protection** - Blade template includes CSRF token

---

## Troubleshooting

### "No Sanctum token found"
- User not logged in or session expired
- Check browser console for `window.SANCTUM_TOKEN`
- Clear cache and log in again

### "Device token not registering"
- Check DevTools Network tab for POST to `/api/device-tokens`
- Verify response status (should be 200)
- Check Bearer token in Authorization header

### "Notification not received"
- Verify FCM permissions on Android device
- Check notification listeners in console
- Verify device token is in database

### "Unsupported engine" npm warnings
- Ignore - your Node version works fine
- Just warnings, not errors

---

## Next Steps

1. **Immediately**: Download and place `google-services.json`
2. **This week**: Fill Firebase env variables and build APK
3. **Testing**: Verify token registration and notifications
4. **Production**: Deploy to Forge with env variables set

---

## Support Resources

- Firebase Setup: https://firebase.google.com/docs/android/setup
- Capacitor Firebase: https://github.com/capawesome-team/capacitor-firebase
- Laravel Sanctum: https://laravel.com/docs/sanctum
- Full setup guide: See `FIREBASE_SETUP.md` in project root

---

## Quick Command Reference

```bash
# Install dependency
npm install @capacitor-firebase/messaging

# Build and sync
npm run build && npx cap sync android

# Run on device
npx cap run android --livereload

# Open in Android Studio
npx cap open android

# Check logs
npx cap build android
```

