# Build APK and Test on Real Mobile Device

Since your web app is already live at https://xpensesettle.on-forge.com/, we can build the APK to load your production app directly!

---

## Step 1: Configure Capacitor for Production URL

Update the Capacitor configuration to use your live website:

File: `capacitor.config.ts`

```typescript
import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.expensesettle.app',
  appName: 'ExpenseSettle',
  webDir: 'public',

  // Configure to load your production URL
  server: {
    url: 'https://xpensesettle.on-forge.com',
    cleartext: false, // HTTPS only
  },

  ios: {
    scheme: 'ExpenseSettle',
  },
  android: {
    buildOptions: {
      keystorePath: '~/.keystore/expensesettle.keystore',
      keystorePassword: process.env.KEYSTORE_PASSWORD,
      keystoreAlias: 'expensesettle',
      keystoreAliasPassword: process.env.KEYSTORE_ALIAS_PASSWORD,
      releaseType: 'APK',
    },
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#ffffff',
      androidScaleType: 'center',
      showSpinner: false,
    },
  },
};

export default config;
```

Or for **quick testing**, just replace the URL in capacitor.config.ts with your production URL.

---

## Step 2: Enable USB Debugging on Your Phone

### For Android Phones:

1. **Open Settings**
2. **Go to:** About Phone (or About Device)
3. **Find:** Build Number (appears near bottom)
4. **Tap** Build Number **7 times** (rapidly)
   - You'll see: "You are now a developer!"
5. **Go back** to Settings
6. **Find:** Developer Options (appears at bottom)
7. **Turn ON:** USB Debugging
8. **Turn ON:** Install from USB (if available)
9. **Turn ON:** File Transfer mode (USB Charging Settings)

---

## Step 3: Connect Phone to Mac via USB

1. **Connect your Android phone** to Mac with USB cable
2. **On your phone**, tap **"Allow"** when asked for USB debugging permission
3. **On Mac**, verify connection:

```bash
adb devices

# Should show:
# List of attached devices
# EMULATOR-ID          device
# YOUR_PHONE_ID        device
```

If you don't see your phone, try:
- Unplug and replug USB cable
- Restart adb:
  ```bash
  adb kill-server
  adb start-server
  adb devices
  ```

---

## Step 4: Build Debug APK

### Option A: Quick Debug Build (Fastest - Recommended for Testing)

```bash
cd /Users/arunkumar/Documents/Application/expenseSettle

# 1. Sync Capacitor with production URL
npx cap copy

# 2. Build debug APK (will open Android Studio)
npx cap build android

# Select "debug" when prompted
# Wait 3-5 minutes for build
```

### Option B: Command Line Build (Faster)

```bash
cd /Users/arunkumar/Documents/Application/expenseSettle/android

# Clean and build
./gradlew clean
./gradlew assembleDebug

# APK location: app/build/outputs/apk/debug/app-debug.apk
```

---

## Step 5: Install APK on Phone

### Method A: Using `adb` (Command Line - Fastest)

```bash
# If you built with Android Studio, find the APK:
ls android/app/build/outputs/apk/debug/

# Install on connected phone:
adb install android/app/build/outputs/apk/debug/app-debug.apk

# Should show:
# Success
```

### Method B: Using Capacitor (Automatic)

```bash
# After building, Capacitor can install automatically:
npx cap run android

# Automatically builds and installs on connected phone
```

### Method C: Manual - Drag and Drop to Phone

```bash
# Copy APK to phone's Downloads folder via USB file transfer
# Then open file manager on phone and tap APK to install
```

---

## Step 6: Test on Your Mobile Device

Once installed:

1. **Find ExpenseSettle** in your app drawer
2. **Tap to open**
3. **Wait 3-5 seconds** for app to load (first time is slower)
4. **Test these features:**
   - ✅ App loads your live website
   - ✅ Can scroll and navigate
   - ✅ Can tap buttons
   - ✅ Login works
   - ✅ Dashboard displays
   - ✅ Can create/view groups
   - ✅ Can add expenses
   - ✅ Can mark payments
   - ✅ Camera works (for receipts)
   - ✅ No network errors

---

## Troubleshooting

### "APK won't install"

```bash
# Try uninstalling old version first
adb uninstall com.expensesettle.app

# Then install again
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

### "Phone won't connect via USB"

```bash
# Restart adb
adb kill-server
adb start-server

# Check again
adb devices

# If still not showing:
# 1. Try different USB cable
# 2. Try different USB port
# 3. Restart phone
# 4. Check that USB debugging is ON in phone settings
```

### "App crashes when opening"

```bash
# Check Android logs
adb logcat | grep -i "expensesettle\|error\|exception"

# Common issues:
# - Network not working (check phone has internet)
# - Wrong URL in capacitor.config.ts
# - Firebase configuration missing (if using notifications)
```

### "App loads but web page shows blank"

```bash
# Check if URL is reachable from phone
# 1. Open Chrome browser on phone
# 2. Go to: https://xpensesettle.on-forge.com
# 3. If page loads, then web works
# 4. If not, check network/firewall

# Check Capacitor logs
adb logcat | grep Capacitor
```

### "Build fails with Gradle error"

```bash
# Clean and rebuild
cd android
./gradlew clean
./gradlew assembleDebug
cd ..

# If still fails, check Java version
java -version
# Should show: openjdk version "11.x.x"
```

---

## What You'll See

### When App Loads Successfully

```
┌─────────────────────────────────┐
│   ExpenseSettle                 │
│   (Loading indicator)           │
├─────────────────────────────────┤
│                                 │
│   (Your website loads here)     │
│   Balance: ₹X                   │
│   Pending: X                    │
│   Your Squads: X                │
│                                 │
│   [All your features visible]   │
│                                 │
└─────────────────────────────────┘
```

### When Testing

```
Test Checklist:
✓ App icon appears
✓ App launches (no crash)
✓ Website loads (no blank screen)
✓ All buttons work
✓ Navigation works
✓ Login works
✓ Can see dashboard
✓ Can view groups
✓ Can add expenses
✓ No error messages
```

---

## Quick Commands

```bash
# Check connected devices
adb devices

# Build debug APK
npx cap build android

# Install on phone
adb install android/app/build/outputs/apk/debug/app-debug.apk

# View logs while testing
adb logcat | grep -i expensesettle

# Uninstall from phone
adb uninstall com.expensesettle.app

# Take screenshot on phone
adb shell screencap -p /sdcard/screenshot.png
adb pull /sdcard/screenshot.png ./screenshot.png

# Clear app data
adb shell pm clear com.expensesettle.app

# Force stop app
adb shell am force-stop com.expensesettle.app
```

---

## App Storage Location on Phone

After installation, your app files are stored at:
```
/data/data/com.expensesettle.app/
```

But you can't directly access this without root. For debugging:
```bash
# View app cache
adb shell ls -la /data/data/com.expensesettle.app/

# Clear app cache
adb shell pm clear com.expensesettle.app
```

---

## Next Steps After Testing

1. ✅ Test all features on your phone
2. ✅ Check performance (is it fast?)
3. ✅ Try with different network (WiFi vs mobile data)
4. ✅ Test login/logout multiple times
5. ✅ Try all buttons and features
6. ✅ Check error handling (try offline mode)
7. ✅ Share with friends to test

Then:
- **Add Push Notifications** → FIREBASE_SETUP.md
- **Setup Camera for Receipts** → Already works!
- **Build Release APK** → For Google Play Store
- **Deploy to Google Play** → DEPLOY_TO_APP_STORES.md

---

## Building Release APK (For Google Play Store)

Once you've tested on your phone with debug APK, you can build release APK:

```bash
# Generate keystore (one time)
keytool -genkey -v -keystore ~/expensesettle.keystore \
  -keyalg RSA -keysize 2048 -validity 10000 \
  -alias expensesettle

# Build release APK
cd android
./gradlew assembleRelease
cd ..

# APK location: android/app/build/outputs/apk/release/app-release.apk
```

See: DEPLOY_TO_APP_STORES.md for full Google Play submission guide.

---

## Common Settings to Test

On your mobile device, make sure to test:

1. **Mobile Network** (4G/5G/LTE)
   - App should work on mobile data
   - Not just WiFi

2. **WiFi**
   - Test on different WiFi networks
   - Public WiFi, home WiFi, etc.

3. **Offline Mode** (optional)
   - Turn off internet
   - App should show graceful error
   - Not just crash

4. **Screen Rotation**
   - Rotate phone to landscape
   - App should rotate (or stay portrait)
   - No layout broken

5. **Multiple Languages** (if applicable)
   - Test in different phone languages
   - App should display correctly

---

**You're ready to build and test!** 🚀

**Start with:** `npx cap build android`

Then follow the steps above to install on your phone.

Report back with results!
