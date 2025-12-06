# APK Build & Mobile Testing - Quick Guide

## 🎯 Your Production App

**Website:** https://xpensesettle.on-forge.com/
**App ID:** com.expensesettle.app
**Target:** Your Android phone

---

## ✅ Pre-requisites (Already Done)

- ✅ Java 11 installed
- ✅ Android Studio installed
- ✅ Capacitor configured for production URL
- ✅ Your web app is live

---

## 🚀 Build APK in 4 Steps

### Step 1: Connect Your Phone (2 minutes)

**On your Android phone:**

1. Open **Settings**
2. Search for **"Build Number"**
3. **Tap Build Number 7 times** quickly
4. Go to **Developer Options**
5. Turn **ON:** USB Debugging
6. Turn **ON:** File Transfer Mode
7. **Connect to Mac** with USB cable
8. Tap **"Allow"** when asked for USB debugging permission

**On your Mac:**

```bash
# Verify phone is connected
adb devices

# Should show:
# List of attached devices
# YOUR_DEVICE_ID          device
```

If phone doesn't show:
```bash
# Restart adb
adb kill-server
adb start-server
adb devices
```

---

### Step 2: Build APK (5 minutes)

```bash
cd /Users/arunkumar/Documents/Application/expenseSettle

# Sync Capacitor with your production URL
npx cap copy

# Build debug APK (fastest for testing)
npx cap build android

# When Android Studio appears:
# - Click "Build" > "Build Bundle(s)/APK(s)" > "Build APK"
# - Or: Select "debug" when prompted
# - Wait 3-5 minutes...
```

**Or use command line (faster):**

```bash
cd android
./gradlew assembleDebug
cd ..
```

**APK will be created at:**
```
android/app/build/outputs/apk/debug/app-debug.apk
```

---

### Step 3: Install APK on Phone (1 minute)

```bash
# Make sure phone is connected
adb devices

# Install the APK
adb install android/app/build/outputs/apk/debug/app-debug.apk

# Should show:
# Success
```

---

### Step 4: Test on Your Phone (5 minutes)

1. **Open your phone**
2. **Find "ExpenseSettle"** in app drawer
3. **Tap to launch** (first launch takes 3-5 seconds)
4. **Wait for app to load**
5. **Test features:**

```
✓ App loads (no blank screen)
✓ Your website appears
✓ Can see dashboard with balance
✓ Can click buttons
✓ Can navigate between pages
✓ Login works
✓ Can view groups
✓ Can add expenses
✓ No error messages
```

---

## 📝 Complete Checklist

### Setup Checklist
- [ ] Java installed: `java -version` shows 11.x.x
- [ ] Android Studio installed and configured
- [ ] Phone USB debugging enabled
- [ ] Phone connected to Mac: `adb devices` shows phone

### Build Checklist
- [ ] Capacitor config points to production URL
- [ ] APK built successfully
- [ ] APK file exists at correct location
- [ ] APK installed on phone without errors

### Testing Checklist
- [ ] App icon appears on phone
- [ ] App launches without crashing
- [ ] Website loads (not blank screen)
- [ ] Dashboard visible with your data
- [ ] Can scroll and interact
- [ ] No error messages in logs
- [ ] All features work

---

## 🐛 Quick Troubleshooting

### "Phone not showing in adb devices"

```bash
adb kill-server
adb start-server
adb devices

# Check phone settings:
# Settings > Developer Options > USB Debugging = ON
# Change USB mode to: File Transfer or File Transfer (MTP)
```

### "APK won't install"

```bash
# Uninstall old version first
adb uninstall com.expensesettle.app

# Then try installing again
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

### "App shows blank screen"

```bash
# Check internet connection on phone
# Try accessing https://xpensesettle.on-forge.com in Chrome on phone

# Check logs for errors
adb logcat | grep -i error
```

### "Gradle build failed"

```bash
cd android
./gradlew clean
./gradlew assembleDebug
cd ..

# If still failing, check Java:
java -version
# Must show: openjdk version "11.x.x"
```

### "App crashes on launch"

```bash
# View crash logs
adb logcat | grep -i "expensesettle\|crash\|exception"

# Force stop and clear
adb shell am force-stop com.expensesettle.app
adb shell pm clear com.expensesettle.app

# Reinstall
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

---

## 📊 Expected Performance

On a real phone, you should expect:

- **Launch time:** 2-5 seconds (first load)
- **Page load:** < 1 second (on mobile data)
- **Scrolling:** Smooth (60 FPS)
- **Button clicks:** Instant response
- **Data loading:** < 2 seconds

If slower than this, check:
- Your internet connection quality
- Phone has enough free RAM
- Laravel server is responding quickly

---

## 🎯 What Happens Inside

When you tap the ExpenseSettle app:

```
Phone → Opens app → Capacitor loads → WebView created →
Loads https://xpensesettle.on-forge.com → Your website appears
```

So it's your actual live website, just wrapped in a native shell!

---

## 🔍 View Logs While Testing

In one terminal, watch logs as you test the app:

```bash
adb logcat | grep -i "expensesettle\|error\|network"

# This shows:
# - Network requests
# - JavaScript errors
# - App crashes
# - Any issues
```

---

## 💾 Multiple APK Versions

You can have multiple test versions:

```bash
# Keep debug APK
cp android/app/build/outputs/apk/debug/app-debug.apk \
   ./app-debug-v1.apk

# Build new version with changes
npm run build
npx cap copy
npx cap build android
```

---

## 🚀 Next Steps After Testing

1. ✅ Confirm app works on your phone
2. ✅ Test all features
3. ✅ Share with friends (use adb install to install on other phones)
4. ✅ Get feedback
5. 🔔 Setup Firebase push notifications (optional)
6. 📦 Build release APK for Google Play Store
7. 🎁 Submit to Google Play Store

---

## Command Reference

```bash
# Check Java
java -version

# Check Android SDK
echo $ANDROID_SDK_ROOT

# Connect to phone
adb devices

# Install APK
adb install android/app/build/outputs/apk/debug/app-debug.apk

# Uninstall app
adb uninstall com.expensesettle.app

# View logs
adb logcat | grep -i error

# Clear app data
adb shell pm clear com.expensesettle.app

# Force stop app
adb shell am force-stop com.expensesettle.app

# Build APK
npx cap build android
# or
cd android && ./gradlew assembleDebug && cd ..

# Sync production URL
npx cap copy
```

---

## Success Looks Like This!

**Terminal output:**
```
$ adb install android/app/build/outputs/apk/debug/app-debug.apk
Success
```

**Phone screen:**
```
┌─────────────────────────┐
│   ExpenseSettle         │
│   ✓ (checkmark icon)    │
├─────────────────────────┤
│                         │
│   Balance: ₹X          │
│   Pending: X           │
│   Groups: X            │
│                         │
│   [All features work]   │
│                         │
└─────────────────────────┘
```

---

## 🎉 You're Ready!

**Start here:** `adb devices`

Then follow the 4 steps above.

**Report back with:**
- Did app build successfully?
- Does app load on your phone?
- What features did you test?

Good luck! 🚀
