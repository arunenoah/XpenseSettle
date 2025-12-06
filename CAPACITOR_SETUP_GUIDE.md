# ExpenseSettle Capacitor Setup Guide

## Overview
This guide walks you through converting your Laravel web app to iOS and Android apps using Capacitor, **without modifying your existing web application**.

---

## Prerequisites

### Required Tools
- **Node.js** v16+ and npm
- **Xcode 14+** (for iOS development)
- **Android Studio** (for Android development)
- **Java Development Kit (JDK)** 11+ (for Android)
- **Cocoapods** (for iOS dependencies)

### Install Prerequisites

#### macOS
```bash
# Install Xcode Command Line Tools
xcode-select --install

# Install Cocoapods
sudo gem install cocoapods

# Install Android Studio
# Download from: https://developer.android.com/studio

# Install Java
brew install java
```

#### Linux/Windows
Follow official guides for Android Studio and Java installation.

---

## Step 1: Install Capacitor (5 minutes)

```bash
# Navigate to your project
cd /Users/arunkumar/Documents/Application/expenseSettle

# Install Capacitor CLI and core packages
npm install --save @capacitor/core @capacitor/cli @capacitor/app @capacitor/haptics @capacitor/keyboard @capacitor/splash-screen @capacitor/status-bar

# Install platform-specific packages
npm install --save @capacitor/ios @capacitor/android

# Install native features (we'll use these)
npm install --save @capacitor/camera @capacitor/device @capacitor/geolocation
npm install --save @capacitor/local-notifications @capacitor/push-notifications
npm install --save @capacitor/storage
```

---

## Step 2: Build Your Web App

Capacitor wraps your built web app, so we need production build:

```bash
# Build your Vite project (generates /public directory)
npm run build

# Verify build succeeded
ls -la public/
# Should see: index.html, js/, css/, images/ etc.
```

---

## Step 3: Initialize Capacitor

```bash
# Initialize Capacitor (uses capacitor.config.ts we created)
npx cap init

# When prompted, use these values:
# App Name: ExpenseSettle
# App ID: com.expensesettle.app
# Web dir: public
```

---

## Step 4: Add iOS Platform

```bash
# Add iOS support
npx cap add ios

# This creates:
# - ios/ directory
# - ExpenseSettle.xcworkspace
# - iOS native configuration
```

### Step 4a: Configure iOS (IMPORTANT!)

Open the iOS project and configure:

```bash
# Open in Xcode
open ios/App/App.xcworkspace

# In Xcode, configure:
# 1. General tab:
#    - Bundle Identifier: com.expensesettle.app
#    - Minimum Deployment: iOS 12.0+
#
# 2. Signing & Capabilities:
#    - Team: Your Apple Developer Team
#    - Provisioning Profile: Automatic
#
# 3. Add capabilities:
#    - Camera (for receipt scanning)
#    - Push Notifications
#    - Background Modes (for sync)
```

---

## Step 5: Add Android Platform

```bash
# Add Android support
npx cap add android

# This creates:
# - android/ directory (Android Studio project)
# - Gradle build files
# - Android configuration
```

### Step 5a: Configure Android

```bash
# Open Android Studio
open -a "Android Studio" android/

# Configure in Android Studio:
# 1. Build > Clean Project
# 2. Gradle > Sync Now
# 3. Project Structure:
#    - SDK Compilation: API 34+
#    - Min SDK: API 24 (Android 7.0+)
#    - Target SDK: API 34+
#
# 4. File > Project Structure:
#    - App module > Signing Configs
#    - Create release keystore (see deployment guide)
```

---

## Step 6: Update Web App Configuration (Critical!)

### Allow Mobile App to Access Your Web App

If your Laravel app runs on different domain/port for mobile, update **capacitor.config.ts**:

```typescript
server: {
  url: 'http://192.168.1.100:8000', // Your Laravel dev server
  cleartext: true, // Allow non-HTTPS during development
},
```

For production, configure your domain.

---

## Step 7: Add App Icons and Splash Screen

```bash
# Create assets directory
mkdir -p resources/ios resources/android

# Add your app icon:
# - iOS: 1024x1024 PNG → resources/ios/icon.png
# - Android: 1024x1024 PNG → resources/android/icon.png
#
# Add splash screen (optional):
# - 2732x2732 PNG → resources/ios/splash.png
# - 1280x720 PNG → resources/android/splash.png

# Generate icons/splashes for all sizes
npm install --save-dev @capacitor/assets
npx cap-assets generate --imagespath ./resources
```

---

## Step 8: Update Android Settings (For Permissions)

File: `android/app/src/main/AndroidManifest.xml`

Add these permissions (Capacitor plugins handle this, but ensure they're there):

```xml
<!-- Already included by Capacitor plugins, verify they exist -->
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

---

## Step 9: Copy Web App to Native Projects

After every build, sync with native:

```bash
# Build web app
npm run build

# Copy to iOS and Android
npx cap copy

# Or full update (if files changed)
npx cap sync
```

---

## Step 10: Build and Test

### iOS (Mac only)

```bash
# Build for iOS
npx cap build ios

# Or open in Xcode and run:
open ios/App/App.xcworkspace
# Select target device/simulator and press Play (Cmd+R)
```

### Android

```bash
# Build for Android
npx cap build android

# Or open in Android Studio:
open -a "Android Studio" android/
# Select emulator or device and click Run
```

---

## Testing on Your Device

### iOS

```bash
# Connect iPhone via USB
# Trust the computer on your device
# In Xcode, select your device from dropdown
# Click Play button to run on device
```

### Android

```bash
# Connect Android phone via USB
# Enable Developer Mode (tap Build Number 7 times in Settings)
# Enable USB Debugging
# In Android Studio, select your device from dropdown
# Click Run to deploy
```

---

## Mobile App Features to Add (Plugin by Plugin)

### 1. Camera for Receipt Scanning

```typescript
// TypeScript/JavaScript example
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';

async function takePicture() {
  const image = await Camera.getPhoto({
    quality: 90,
    allowEditing: false,
    resultType: CameraResultType.Uri,
    source: CameraSource.Camera,
  });

  // image.webPath has the photo URL
  // Send to server for OCR processing
}
```

### 2. Biometric Authentication

```bash
npm install --save @capacitor/native-audio native-audio
```

### 3. Push Notifications

```bash
npm install --save @capacitor/push-notifications

# Also setup Firebase Cloud Messaging
# See FIREBASE_SETUP.md
```

### 4. Offline Storage

```bash
# Already installed: @capacitor/storage
# Uses localStorage on web, secure native storage on mobile

import { Storage } from '@capacitor/storage';

// Save
await Storage.set({
  key: 'user_token',
  value: 'abc123...',
});

// Retrieve
const { value } = await Storage.get({ key: 'user_token' });
```

---

## Development Workflow

### Iterating on Web App

```bash
# 1. Make changes to your Blade templates
# 2. Rebuild
npm run build

# 3. Sync with native projects
npx cap copy

# 4. Run on simulator/device
npx cap run ios   # or android
```

### Quick Dev Server (Optional)

For faster iteration during development:

```bash
# In one terminal, run Vite dev server
npm run dev

# In capacitor.config.ts, point to it:
server: {
  url: 'http://localhost:5173',
  cleartext: true,
}

# Then deploy your app to simulator
# It will reload from dev server automatically
```

---

## Deployment Checklist

### Before Publishing

- [ ] Icon: 1024x1024 PNG for App Store/Play Store
- [ ] Screenshots: 5-8 screenshots of app
- [ ] Description: Clear app description
- [ ] Privacy Policy: HTTPS URL to privacy policy
- [ ] App ID: `com.expensesettle.app`
- [ ] Version Number: Start with 1.0.0
- [ ] Keystore: Generate signing keystore for Android
- [ ] Certificates: Obtain from Apple Developer

### iOS Deployment
See: `iOS_DEPLOYMENT.md`

### Android Deployment
See: `ANDROID_DEPLOYMENT.md`

---

## Troubleshooting

### Issue: "Cannot find node_modules/@capacitor/core"

```bash
npm install
npx cap sync
```

### Issue: "iOS build fails with 'pod install' error"

```bash
cd ios/App
pod update
cd ../../
npx cap copy ios
```

### Issue: "Android build fails with 'gradle sync' error"

```bash
rm -rf android/.gradle
cd android
./gradlew clean
cd ..
npx cap sync android
```

### Issue: "App won't load my web app"

Check:
1. `capacitor.config.ts` has correct `webDir: 'public'`
2. `npm run build` completed successfully
3. `npx cap copy` was run
4. Network is accessible (if using `localhost`, won't work on device)

### Issue: "Camera/Notification permissions not working"

```bash
# iOS: Check Xcode > Signing & Capabilities > add permissions
# Android: Verify AndroidManifest.xml has permissions
# Both: Run `npx cap copy` and rebuild
```

---

## Next Steps

1. **Setup Laravel** for mobile app (CORS, API endpoints) - `LARAVEL_MOBILE_SETUP.md`
2. **Add Push Notifications** via Firebase - `FIREBASE_SETUP.md`
3. **Build for Release** - `iOS_DEPLOYMENT.md` and `ANDROID_DEPLOYMENT.md`
4. **Submit to Stores** - App Store and Google Play Store guides

---

## Quick Commands Reference

```bash
# Build web app
npm run build

# Sync with native (after building)
npx cap copy

# Run on iOS simulator
npx cap run ios

# Run on Android emulator
npx cap run android

# Open iOS in Xcode
npx cap open ios

# Open Android in Android Studio
npx cap open android

# Update native platforms
npx cap sync

# Check app version
npx cap --version
```

---

## Important Notes

⚠️ **Your web app is loaded in a WebView** - it's like an in-app browser
- Same code runs on web and mobile
- Access to native features via Capacitor plugins
- No separate API needed (but can be added later)
- Native features require permission requests

---

## Support Resources

- Capacitor Docs: https://capacitorjs.com/docs
- Community Forum: https://github.com/ionic-team/capacitor/discussions
- Capacitor Plugins: https://capacitorjs.com/docs/plugins
- Stack Overflow: Tag `capacitor`

---

**Ready to start? Run Step 1-5 above, then let me know you completed them!** 🚀
