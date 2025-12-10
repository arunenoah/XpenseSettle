# Firebase Push Notifications Setup

Complete guide to set up push notifications for ExpenseSettle mobile app.

---

## 📋 What You'll Have at the End

- ✅ Android app receives push notifications
- ✅ Laravel backend can send notifications
- ✅ Device tokens stored securely
- ✅ Notifications work for payments, group invites, and expenses

---

## Step 1: Create Firebase Project

1. Go to: **https://console.firebase.google.com**
2. Click **"Create a new project"**
3. Project name: `ExpenseSettle`
4. Accept terms and click **"Create project"**
5. Wait for project to initialize (~1-2 minutes)

---

## Step 2: Create Android App in Firebase

1. In Firebase Console, click **"Create app"** → Select **"Android"**
2. Fill in:
   - **Android package name:** `com.expensesettle.app`
   - **App nickname:** `ExpenseSettle Mobile`
   - **SHA-1 certificate hash:** (get this next step, leave blank for now)
3. Click **"Register app"**
4. Download **`google-services.json`** file
5. Save to: `android/app/google-services.json` in your project

---

## Step 3: Get SHA-1 Certificate (For Release APK Later)

```bash
# Generate SHA-1 key (you'll need this when publishing to Google Play Store)
cd android
./gradlew signingReport

# Look for: Variant: release, SHA1: xxxxxxxxxxxxxxxx...
# Add this SHA1 to Firebase Console later
```

---

## Step 4: Configure Laravel Backend

### Update .env File

Add these to your `.env` file:

```env
# Firebase Configuration
FIREBASE_PROJECT_ID=your-project-id-here
FIREBASE_CREDENTIALS_FILE=firebase-credentials.json
FIREBASE_API_KEY=your-api-key-here
FCM_SERVER_KEY=your-fcm-server-key-here
```

### Get Firebase Credentials

1. In Firebase Console, click **⚙️ (Settings)** → **Project Settings**
2. Go to **"Service Accounts"** tab
3. Click **"Generate New Private Key"**
4. A JSON file downloads
5. Rename it to `firebase-credentials.json`
6. Place it in: `storage/app/firebase-credentials.json` (keep it secure!)

### Get API Keys

1. In Firebase Console, go to **"Settings"** → **"Project Settings"**
2. Go to **"General"** tab
3. Copy **"Web API Key"**
4. Add to `.env` as `FIREBASE_API_KEY`

---

## Step 5: Run Database Migration

Create the device_tokens table:

```bash
php artisan migrate
```

This creates a table to store user device tokens.

---

## Step 6: Update Android App Code

The mobile app needs to register its device token when it starts.

### In your React/Frontend code:

```javascript
import { PushNotifications } from '@capacitor/push-notifications';

// When app initializes and user is logged in:
async function registerForPushNotifications() {
  try {
    // Request permission
    const permStatus = await PushNotifications.requestPermissions();
    if (permStatus.receive === 'prompt') {
      await PushNotifications.requestPermissions();
    }

    // Get the FCM token
    const result = await PushNotifications.getDeliveryTokens();
    const token = result.fcmTokens[0];

    // Send token to Laravel backend
    const response = await fetch('/api/device-tokens', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${userToken}`, // Your auth token
      },
      body: JSON.stringify({
        token: token,
        device_name: 'Samsung S25 Ultra', // Get actual device name
        device_type: 'android',
        app_version: '1.0.0',
      }),
    });

    if (response.ok) {
      console.log('Device token registered!');
    }
  } catch (error) {
    console.error('Failed to register device token', error);
  }
}

// Listen for incoming notifications
PushNotifications.addListener('pushNotificationReceived', (notification) => {
  console.log('Notification received:', notification);
  // Show notification UI
});
```

---

## Step 7: Test Push Notifications

### Create a Test Notification in Laravel

```php
// Create a test route in routes/web.php or routes/api.php
use App\Services\FirebaseService;

Route::get('/test-notification', function () {
    $firebase = new FirebaseService();

    // Get a user's device token from database
    $deviceToken = auth()->user()->deviceTokens()->active()->first()?->token;

    if (!$deviceToken) {
        return response()->json(['error' => 'No device token found'], 404);
    }

    // Send test notification
    $result = $firebase->sendNotification(
        $deviceToken,
        'Test Notification',
        'This is a test push notification!',
        ['test' => 'true']
    );

    return response()->json([
        'message' => 'Notification sent!',
        'success' => $result,
    ]);
});
```

### Test from Browser

1. Login to your app: https://xpensesettle.on-forge.com/
2. Visit: https://xpensesettle.on-forge.com/test-notification
3. Check your phone - you should see the notification!

---

## Step 8: Implement Real Notifications

### When Payment is Made

In your payment processing code:

```php
use App\Services\FirebaseService;
use App\Models\DeviceToken;

// After creating a payment in database:
$payment = Payment::create($paymentData);

// Get recipient's device tokens
$recipientTokens = DeviceToken::where('user_id', $payment->recipient_id)
    ->active()
    ->pluck('token')
    ->toArray();

// Send notification
if (!empty($recipientTokens)) {
    $firebase = new FirebaseService();
    $firebase->notifyPaymentMade(
        $recipientTokens[0],
        $payment->payer->name,
        $payment->amount
    );
}
```

### When User is Added to Group

```php
use App\Services\FirebaseService;

// After adding user to group:
$member = GroupMember::create($groupMemberData);
$tokens = $member->user->deviceTokens()->active()->pluck('token')->toArray();

if (!empty($tokens)) {
    $firebase = new FirebaseService();
    $firebase->notifyGroupInvite(
        $tokens[0],
        $member->group->name,
        auth()->user()->name
    );
}
```

---

## Step 9: Production Setup

When ready to release on Google Play Store:

1. **Generate Release Keystore**
   ```bash
   keytool -genkey -v -keystore ~/.keystore/expensesettle.keystore \
     -keyalg RSA -keysize 2048 -validity 10000 \
     -alias expensesettle
   ```

2. **Get SHA-1 from Release Keystore**
   ```bash
   keytool -list -v -keystore ~/.keystore/expensesettle.keystore \
     -alias expensesettle
   ```

3. **Add to Firebase Console**
   - Firebase Settings → SHA-1 from above → Save

4. **Build Release APK**
   ```bash
   export JAVA_HOME=/opt/homebrew/opt/openjdk@21
   cd android
   ./gradlew assembleRelease
   ```

---

## Troubleshooting

### "Google-services.json not found"

Make sure you:
1. Downloaded `google-services.json` from Firebase
2. Placed it in: `android/app/google-services.json`
3. Spelled the filename correctly (case-sensitive)

### "Failed to get Firebase credentials"

Check:
1. `firebase-credentials.json` is in `storage/app/`
2. File path in `.env` is correct
3. File has proper permissions (readable)

### "Device token not registered"

The mobile app needs to:
1. Be logged in
2. Have permission to send notifications (allow when prompted)
3. Have internet connection
4. Call the `/api/device-tokens` endpoint

### "Notification not received"

Check:
1. Device token is stored in database: `SELECT * FROM device_tokens`
2. Notification was actually sent in Laravel logs
3. App has notification permission enabled
4. Device has internet connection

---

## API Endpoints

### Register Device Token

```
POST /api/device-tokens
Headers:
  Authorization: Bearer {user_token}
  Content-Type: application/json

Body:
{
  "token": "firebase_token_here",
  "device_name": "Samsung S25 Ultra",
  "device_type": "android",
  "app_version": "1.0.0"
}

Response:
{
  "message": "Device token registered",
  "device_token": {
    "id": 1,
    "user_id": 1,
    "token": "firebase_token_here",
    ...
  }
}
```

### Get User's Device Tokens

```
GET /api/device-tokens
Headers:
  Authorization: Bearer {user_token}

Response:
{
  "count": 1,
  "device_tokens": [...]
}
```

### Remove Device Token

```
DELETE /api/device-tokens
Headers:
  Authorization: Bearer {user_token}
  Content-Type: application/json

Body:
{
  "token": "firebase_token_here"
}

Response:
{
  "message": "Device token removed"
}
```

---

## Security Best Practices

1. **Secure the credentials file:**
   - Never commit `firebase-credentials.json` to git
   - Add to `.gitignore`
   - Store in `storage/app/` (not web-accessible)

2. **Validate tokens:**
   - Always validate token format before storing
   - Remove inactive tokens periodically

3. **Rate limit notifications:**
   - Don't send too many notifications (user will disable)
   - Batch notifications when possible

4. **User preferences:**
   - Store user notification preferences
   - Allow users to disable notifications

---

## Files Created

- `app/Services/FirebaseService.php` - Service to send notifications
- `config/firebase.php` - Firebase configuration
- `app/Models/DeviceToken.php` - Database model for tokens
- `app/Http/Controllers/Api/DeviceTokenController.php` - API endpoints
- `routes/api.php` - API routes
- `database/migrations/2024_12_07_000000_create_device_tokens_table.php` - Database table
- `android/app/build.gradle` - Firebase messaging dependency added

---

## Next Steps

1. ✅ Create Firebase project
2. ✅ Get google-services.json and credentials
3. ✅ Update .env with Firebase config
4. ✅ Run database migration
5. ✅ Update mobile app code
6. ✅ Test notifications
7. ✅ Implement in business logic
8. ✅ Deploy to production

---

## Resources

- Firebase Cloud Messaging Docs: https://firebase.google.com/docs/cloud-messaging
- Capacitor Push Notifications: https://capacitorjs.com/docs/apis/push-notifications
- Firebase Console: https://console.firebase.google.com
- Google Cloud Console: https://console.cloud.google.com

---

**Push notifications are now ready to use!** 🔔
