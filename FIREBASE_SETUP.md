# Firebase Setup for Push Notifications

Push notifications allow your app to notify users when payments are made, groups are updated, etc.

---

## Prerequisites

- Firebase Account (free at https://console.firebase.google.com)
- Apple Developer Account (for iOS)
- Google Play Developer Account (for Android)

---

## Step 1: Create Firebase Project

1. Go to https://console.firebase.google.com
2. Click "Create Project"
3. Name: `ExpenseSettle`
4. Enable Google Analytics (optional)
5. Click "Create Project"

---

## Step 2: Setup Android Firebase

### Add Android App to Firebase

1. In Firebase Console, click "Add App" → "Android"
2. Package Name: `com.expensesettle.app`
3. App Nickname: `ExpenseSettle Mobile`
4. Download `google-services.json`
5. Place in: `android/app/google-services.json`

### Configure Android Build Files

File: `android/build.gradle`

```gradle
buildscript {
    dependencies {
        // Add Google Services
        classpath 'com.google.gms:google-services:4.3.15'
    }
}
```

File: `android/app/build.gradle`

```gradle
// At the bottom of file, add:
apply plugin: 'com.google.gms.google-services'

dependencies {
    // Firebase Cloud Messaging
    implementation 'com.google.firebase:firebase-messaging:23.2.1'
}
```

---

## Step 3: Setup iOS Firebase

### Add iOS App to Firebase

1. In Firebase Console, click "Add App" → "iOS"
2. Bundle ID: `com.expensesettle.app`
3. App Nickname: `ExpenseSettle iOS`
4. Download `GoogleService-Info.plist`
5. Open `ios/App/App.xcworkspace` in Xcode
6. Drag `GoogleService-Info.plist` into Xcode
7. Check "Copy items if needed"
8. Finish

### Configure Xcode

In Xcode (ios/App/App.xcworkspace):

1. Select "App" project
2. Go to "Build Phases"
3. Add new "Run Script Phase"
4. Paste:
   ```bash
   ${PODS_ROOT}/FirebaseCore/Sources/FirebaseCore/Support/run_firebase_setup.sh
   ```

---

## Step 4: Install Capacitor Firebase Plugin

```bash
# Install push notifications plugin
npm install @capacitor/push-notifications

# Install Firebase admin SDK for Laravel
composer require kreait/firebase-php

# Sync with native projects
npx cap sync
```

---

## Step 5: Get Firebase Service Account Key (for Laravel)

### Download Service Account Key

1. In Firebase Console, go to Project Settings (⚙️)
2. Click "Service Accounts" tab
3. Click "Generate New Private Key"
4. Save as `firebase-credentials.json`

### Add to Laravel

```bash
# Create config directory
mkdir -p config/firebase

# Move credentials
cp firebase-credentials.json config/firebase/

# Add to .gitignore
echo "config/firebase/firebase-credentials.json" >> .gitignore
```

### Create Laravel Firebase Service Class

File: `app/Services/FirebaseService.php`

```php
<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseService
{
    private $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(config_path('firebase/firebase-credentials.json'));

        $this->messaging = $factory->createMessaging();
    }

    /**
     * Send push notification to user
     */
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(
                    \Kreait\Firebase\Messaging\Notification::create()
                        ->withTitle($title)
                        ->withBody($body)
                )
                ->withData($data);

            return $this->messaging->send($message);
        } catch (\Exception $e) {
            \Log::error('Firebase notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send to multiple devices
     */
    public function sendBroadcast(array $deviceTokens, string $title, string $body, array $data = [])
    {
        try {
            $message = CloudMessage::new()
                ->withNotification(
                    \Kreait\Firebase\Messaging\Notification::create()
                        ->withTitle($title)
                        ->withBody($body)
                )
                ->withData($data);

            return $this->messaging->sendMulticast($message, $deviceTokens);
        } catch (\Exception $e) {
            \Log::error('Firebase broadcast error: ' . $e->getMessage());
            return false;
        }
    }
}
```

---

## Step 6: Store Device Tokens in Laravel

When user logs in from mobile app, save their device token:

### Create Migration

```bash
php artisan make:migration add_device_token_to_users
```

File: `database/migrations/YYYY_MM_DD_add_device_token_to_users.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_token')->nullable();
            $table->enum('device_type', ['ios', 'android'])->nullable();
            $table->timestamp('token_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['device_token', 'device_type', 'token_updated_at']);
        });
    }
};
```

Run migration:

```bash
php artisan migrate
```

### Update User Model

File: `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_token',
        'device_type',
    ];

    // Save device token
    public function updateDeviceToken(string $token, string $deviceType = 'android')
    {
        $this->update([
            'device_token' => $token,
            'device_type' => $deviceType,
            'token_updated_at' => now(),
        ]);
    }

    // Get notification method
    public function notifyViaFirebase(string $title, string $body, array $data = [])
    {
        if ($this->device_token) {
            $firebase = new \App\Services\FirebaseService();
            $firebase->sendNotification($this->device_token, $title, $body, $data);
        }
    }
}
```

### Create API Endpoint to Save Token

File: `routes/web.php`

```php
Route::post('/api/device-token', function (Request $request) {
    $user = auth()->user();
    if ($user) {
        $user->updateDeviceToken(
            $request->input('token'),
            $request->input('device_type', 'android')
        );
        return response()->json(['success' => true]);
    }
    return response()->json(['error' => 'Not authenticated'], 401);
});
```

---

## Step 7: Setup Notifications in Web App

Add this to your main Blade template or layout:

File: `resources/views/layouts/app.blade.php`

```html
<script>
// Register device token when user logs in
document.addEventListener('DOMContentLoaded', async function() {
    if (typeof window.Capacitor !== 'undefined' && auth.user) {
        // This is Capacitor app with authenticated user
        setupPushNotifications();
    }
});

async function setupPushNotifications() {
    try {
        const { PushNotifications } = window.Capacitor.Plugins;

        // Request permission
        const permission = await PushNotifications.requestPermissions();

        if (permission.receive === 'granted') {
            // Register to receive push notifications
            await PushNotifications.register();

            // Get the token
            const { value } = await PushNotifications.getDeliveredNotifications();

            // Listen for notifications
            PushNotifications.addListener('registration', (token) => {
                // Save token to server
                fetch('/api/device-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        token: token.value,
                        device_type: window.Capacitor.platform === 'ios' ? 'ios' : 'android',
                    }),
                });

                console.log('Device Token:', token.value);
            });

            // Handle notification when app is in foreground
            PushNotifications.addListener('pushNotificationReceived', (notification) => {
                console.log('Notification received:', notification);
                showNotificationBanner(notification.title, notification.body);
            });

            // Handle notification click
            PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
                const data = action.notification.data;

                if (data.type === 'group') {
                    // Navigate to group
                    window.location.href = `/groups/${data.id}`;
                } else if (data.type === 'expense') {
                    // Navigate to expense
                    window.location.href = `/expenses/${data.id}`;
                } else if (data.type === 'payment') {
                    // Navigate to payment
                    window.location.href = `/groups/${data.group_id}/payments`;
                }
            });
        }
    } catch (error) {
        console.error('Push notification setup error:', error);
    }
}

function showNotificationBanner(title, body) {
    // Show a banner notification in-app
    const banner = document.createElement('div');
    banner.className = 'fixed top-0 left-0 right-0 bg-blue-500 text-white p-4 rounded-b shadow-lg';
    banner.innerHTML = `<strong>${title}</strong><p>${body}</p>`;
    document.body.appendChild(banner);

    setTimeout(() => banner.remove(), 5000);
}
</script>
```

---

## Step 8: Send Notifications from Laravel

Example: When payment is marked as paid

File: `app/Http/Controllers/PaymentController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\FirebaseService;

class PaymentController extends Controller
{
    public function markAsPaid(Payment $payment)
    {
        // Mark as paid
        $payment->update(['status' => 'paid']);

        // Send notification to expense payer
        $payer = $payment->split->expense->payer;
        $firebase = new FirebaseService();

        $firebase->sendNotification(
            deviceToken: $payer->device_token,
            title: 'Payment Received',
            body: auth()->user()->name . ' paid ₹' . $payment->split->share_amount,
            data: [
                'type' => 'payment',
                'payment_id' => $payment->id,
                'group_id' => $payment->split->expense->group_id,
            ]
        );

        return redirect()->back()->with('success', 'Payment marked as paid!');
    }
}
```

---

## Step 9: Build and Test

### Test on iOS Simulator

```bash
# Build and run
npm run build
npx cap copy
npx cap run ios

# In simulator:
# 1. Log in to your app
# 2. Device token should save automatically
# 3. Check Laravel logs for token saved
```

### Test on Android Emulator

```bash
# Build and run
npm run build
npx cap copy
npx cap run android

# In emulator:
# 1. Log in to your app
# 2. Grant notification permission
# 3. Device token should save automatically
```

### Send Test Notification

From Firebase Console:

1. Go to Messaging → Create Campaign
2. Select "Cloud Messaging"
3. Add notification title and body
4. Target: User Segment or Custom Audience
5. Click "Create"

Or use Laravel Artisan:

```php
// In tinker or command
$user = User::find(1);
$firebase = new \App\Services\FirebaseService();
$firebase->sendNotification(
    $user->device_token,
    'Test Notification',
    'This is a test!',
    ['type' => 'test']
);
```

---

## Checklist

- [ ] Firebase project created
- [ ] Android app added to Firebase
- [ ] iOS app added to Firebase
- [ ] google-services.json added to Android
- [ ] GoogleService-Info.plist added to iOS
- [ ] Capacitor push notifications installed
- [ ] Firebase admin SDK installed in Laravel
- [ ] Service account key added to Laravel
- [ ] Device token migration created
- [ ] User model updated
- [ ] API endpoint created for device token
- [ ] Push notification setup code added to app
- [ ] Tested on iOS simulator
- [ ] Tested on Android emulator
- [ ] Test notification sent and received

---

**Push notifications are now ready!** 🎉

Next: Test locally, then deploy to app stores.
