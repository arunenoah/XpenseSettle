# Firebase Push Notifications Setup Guide

## Summary of Implementation

Your ExpenseSettle app now has complete Firebase push notification support for the Android Capacitor app. Here's what has been done:

### Backend Setup ✅
- Modified `AuthController.php` to create Sanctum API tokens on login
- Added Firebase initialization script to Blade layout (`app.blade.php`)
- Exposed authentication token to JavaScript for API calls
- Configured Firebase credentials via environment variables (.env)

### Mobile (Capacitor) Setup ✅
- Installed `@capacitor-firebase/messaging@7.4.0` npm package
- Synced with Android native project

### What's Left - **NEXT STEP**
- Add `google-services.json` to your Android project

---

## Step 1: Get google-services.json from Firebase Console

### 1. Go to Firebase Console
1. Navigate to https://console.firebase.google.com
2. Select your **expensesettle** project

### 2. Download the JSON File
1. Click **⚙️ Project Settings** (gear icon top-left)
2. Go to **Cloud Messaging** tab
3. Or go to **Project Settings** → **Your apps** → Select **Android app**
4. Click **Download google-services.json** button
5. A JSON file will be downloaded to your computer

### 3. Copy to Android Project
1. Copy the downloaded `google-services.json` file
2. Paste it into: `android/app/google-services.json`

**Important**: The file MUST be at `android/app/` NOT `android/` or any other location.

---

## Step 2: Configure Environment Variables

### Local Testing (.env)
Your `.env` already has Firebase configuration placeholders. Make sure these are filled:

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

Get these values from:
- **Firebase Console** → **Project Settings** → **Service Accounts** tab
- Click **Generate New Private Key** to get credentials
- Copy the JSON and extract the values

### Production (Laravel Forge)
1. Go to your Forge app dashboard
2. Click **Environment** section
3. Add the same variables as shown above
4. Use the same values from Firebase Console

---

## Step 3: Build and Test on Android

### Build the Web Assets
```bash
npm run build
```

### Copy to Capacitor
```bash
npx cap copy android
```

### Run on Android Device
```bash
npx cap run android --livereload
```

Or use Android Studio:
```bash
npx cap open android
```

Then build and run from Android Studio.

---

## Step 4: Test the Push Notification Flow

### 1. Log in to the Android App
- Enter your 6-digit PIN
- You should see a success message

### 2. Check Device Token Registration
1. Open Android Logcat (if using Android Studio)
2. Look for log messages:
   ```
   ✅ Capacitor + Token detected - Setting up Firebase...
   📱 Requesting notification permissions...
   ✅ Notifications allowed
   🔑 Device Token: abc123xyz...
   📤 Registering token with backend...
   ✅ Token registered: Device token saved
   ```

### 3. Test Notification Sending
1. In the web app, go to any group
2. Click **📊 Summary**
3. Click **⏳ Mark Paid** on any settlement
4. Complete the payment confirmation
5. Check your Android device - you should see a **green notification banner**

### 4. Verify Device Token in Database
Run this in your Laravel app:
```bash
php artisan tinker
```

Then:
```php
$user = User::first();
$user->deviceTokens()->get();
// Should show registered tokens with timestamps
```

---

## How It Works

### 1. User Logs In (Session + API Token)
```
1. User enters 6-digit PIN
2. Laravel AuthController creates session (PIN-based auth)
3. AuthController also creates Sanctum API token
4. Token exposed to Blade template as window.SANCTUM_TOKEN
```

### 2. Capacitor App Detects Environment
```
1. JavaScript checks if window.Capacitor exists
2. Checks if window.SANCTUM_TOKEN is available
3. If both exist, initializes Firebase
```

### 3. Device Token Registration
```
1. App requests notification permissions from user
2. Firebase Cloud Messaging provides device token
3. App calls POST /api/device-tokens with Bearer token
4. Backend saves token to database
5. Token stored with user association + timestamp
```

### 4. Push Notification Delivery
```
1. Backend detects settlement confirmation
2. Fetches user's active device tokens
3. Sends notification via Firebase Cloud Messaging
4. FCM delivers to device
5. App shows green banner notification
6. User can tap to navigate to group summary
```

---

## File Changes Summary

### Backend Files Modified
- `app/Http/Controllers/AuthController.php` - Added Sanctum token creation
- `resources/views/layouts/app.blade.php` - Added Firebase initialization script

### Frontend Configuration
- `package.json` - Added @capacitor-firebase/messaging
- `capacitor.config.json` - Updated with Firebase plugin

### Android Native
- `android/app/google-services.json` - **NEEDS TO BE ADDED BY YOU**
- `android/app/build.gradle` - Updated by Capacitor
- `android/app/src/main/AndroidManifest.xml` - Updated by Capacitor

### Environment Configuration
- `.env` - FIREBASE_* variables (fill with your credentials)
- `config/firebase.php` - Builds credentials from env variables

---

## Troubleshooting

### Issue: "No Sanctum token found - skipping registration"
**Solution**: The user is not logged in or session expired
- Clear browser cache
- Log in again
- Check `window.SANCTUM_TOKEN` in browser console

### Issue: Device token not registering
**Solution**: Check network request in browser DevTools
- Open DevTools → Network tab
- Log in again
- Look for POST request to `/api/device-tokens`
- Check response status (should be 200)

### Issue: Notification received but app doesn't respond
**Solution**: Check listener setup in browser console
- Look for "✅ Notification listeners ready" message
- If not present, Firebase initialization failed

### Issue: "Unsupported engine" npm warnings
**Solution**: These are just warnings, not errors
- Your Node version (22.11.0) works fine
- You can safely ignore these warnings

---

## Next Steps

1. **Download google-services.json** from Firebase Console
2. **Copy to `android/app/google-services.json`**
3. **Fill .env variables** with Firebase credentials
4. **Build and test** on Android device
5. **Monitor logs** to verify token registration
6. **Mark a payment** to trigger test notification

---

## Quick Reference Commands

```bash
# Install Firebase plugin
npm install @capacitor-firebase/messaging

# Sync with Android
npx cap sync android

# Build web assets
npm run build

# Copy to Capacitor
npx cap copy android

# Run on device (development)
npx cap run android --livereload

# Open in Android Studio
npx cap open android

# Check logs
npx cap build android
```

---

## Production Deployment (Laravel Forge)

When deploying to production:

1. **Set environment variables** in Forge dashboard for:
   - All FIREBASE_* variables
   - FCM_SERVER_KEY
   - FIREBASE_API_KEY
   - FIREBASE_WEB_API_KEY

2. **Build APK/AAB** with production Firebase config:
   ```bash
   npm run build
   npx cap copy android
   # Then build in Android Studio
   ```

3. **Update google-services.json** if using different Firebase project for production

4. **Test** notification flow in production environment

---

## Support

If you need help with:
- Firebase Console setup: https://firebase.google.com/docs/android/setup
- Capacitor Firebase: https://github.com/capawesome-team/capacitor-firebase
- Laravel Sanctum: https://laravel.com/docs/sanctum
