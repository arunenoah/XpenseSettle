# Android Emulator Setup - Step by Step

Complete guide to create and run an Android emulator for testing ExpenseSettle.

---

## Step 1: Download Android Studio

### For Apple Silicon (M1/M2/M3) - Recommended

1. Go to: https://developer.android.com/studio
2. Click **"Download Android Studio"**
3. Select **"Apple Silicon"** version
4. Wait for download
5. Open the `.dmg` file
6. Drag **Android Studio** to Applications folder
7. Wait 2-3 minutes for installation

### For Intel Mac

1. Same as above but select **"Intel"** version instead

---

## Step 2: Launch Android Studio (First Time)

```bash
open -a "Android Studio"
```

You'll see this welcome screen:

```
┌─────────────────────────────────┐
│   Welcome to Android Studio      │
│                                 │
│   [Setup Wizard]               │
│   - Install SDK Components      │
│   - Accept Licenses             │
│   - Download Tools              │
└─────────────────────────────────┘
```

### Follow Setup Wizard

**Screen 1: Welcome**
- Click **"Next"**

**Screen 2: Install Type**
- Select **"Standard"** (recommended)
- Click **"Next"**

**Screen 3: Select Components**
- Ensure these are checked:
  - ✅ Android SDK
  - ✅ Android SDK Platform
  - ✅ Android Virtual Device
  - ✅ Android Emulator
  - ✅ Intel HAXM (if Intel Mac)
- Click **"Next"**

**Screen 4: Android SDK**
- License path: (auto-filled)
- Click **"Finish"**
- Wait 5-10 minutes for downloads

**Screen 5: Emulator Settings**
- Default settings are fine
- Click **"Next"** → **"Finish"**

---

## Step 3: Create Virtual Device (Emulator)

Once Android Studio finishes setup:

### Option A: Using Android Studio GUI (Easiest)

1. **Open Android Studio**
2. Click **"Tools"** (top menu)
3. Click **"Device Manager"**
4. Click **"Create Device"** button

You'll see device selection:

```
Device Selection:
─────────────────────────────────
Category               Model
─────────────────────────────────
Phone    ┌─ Pixel 3
         ├─ Pixel 4
         ├─ Pixel 5  ← Good for testing
         ├─ Pixel 6
         └─ Pixel 7

Tablet   ┌─ Pixel Tablet
         └─ ...

Wear OS  └─ ...
```

**Select:** Pixel 5 → Click **"Next"**

You'll see system image selection:

```
System Images:
────────────────────────────────
Release Name    API Level
────────────────────────────────
Android 13         API 33  ← Good
Android 14         API 34  ← Better
Android 15         API 35  ← Best
```

**Select:** Android 14 (API 34) → Click **"Next"**

Final confirmation:

```
Android Virtual Device
─────────────────────────────────
Device Name: Pixel_5
Processor: ARM64 (because you have Apple Silicon)
RAM: 2048 MB
VM Heap: 512 MB
```

- Click **"Finish"**

### Option B: Using Command Line

```bash
# Create emulator named Pixel_5
$ANDROID_SDK_ROOT/cmdline-tools/bin/avdmanager create avd \
  -n Pixel_5 \
  -k "system-images;android-34;google_apis;arm64-v8a" \
  -d "Pixel 5"
```

---

## Step 4: Start the Emulator

### Option A: From Android Studio

1. **Device Manager** → (right side of Pixel_5)
2. Click **Play ▶️ button**
3. Wait 30-60 seconds for boot

You'll see:

```
┌────────────────────────────────┐
│      Pixel_5 Emulator           │
├────────────────────────────────┤
│                                │
│    (Android boot screen)       │
│    ...                         │
│    Android Lock Screen         │
│                                │
│  Swipe up to unlock →          │
└────────────────────────────────┘
```

### Option B: Command Line

```bash
# Start emulator
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &

# Wait for boot (30-60 seconds)
sleep 45

# Check it's running
adb devices
# Should show: Pixel_5 device
```

---

## Step 5: Verify Emulator is Ready

### Check via Command Line

```bash
# List all devices
adb devices

# Should show:
# List of attached devices
# emulator-5554          device
# Pixel_5                device
```

### Check via Android Studio

Device Manager shows device with green checkmark ✓

---

## Step 6: Unlock Emulator (Optional)

```bash
# Send unlock command
adb shell input keyevent 82

# Or swipe up in emulator GUI
```

---

## Step 7: Install Your App

Once emulator is running and unlocked:

### From Your Project

```bash
cd /Users/arunkumar/Documents/Application/expenseSettle

# Build web app
npm run build

# Sync with native
npx cap copy

# Deploy to Android emulator
npx cap run android

# Wait 1-2 minutes for app to build and install
```

You'll see:

```
[success] Android tooling has been installed!

Building the Gradle project...

Gradle Build Successful!

Launching app on Pixel_5...

✓ ExpenseSettle app installed on emulator
```

---

## Step 8: Test Your App

In the emulator:

1. **Swipe up** to unlock
2. **Find ExpenseSettle** in app drawer (look for icon)
3. **Tap to open**
4. **Test features:**
   - ✅ Page loads
   - ✅ Can navigate between pages
   - ✅ Dashboard displays
   - ✅ Can interact with buttons

---

## Common Issues & Fixes

### "Emulator won't start"

```bash
# Kill all emulator processes
killall emulator

# Start fresh
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &
```

### "adb: command not found"

```bash
# Add to PATH
echo 'export PATH=$ANDROID_SDK_ROOT/platform-tools:$PATH' >> ~/.zshrc
source ~/.zshrc
```

### "App won't install"

```bash
# Check device is ready
adb devices

# Try running again
npx cap run android

# Or manually build
cd android
./gradlew clean
./gradlew build
cd ..
npx cap run android
```

### "Emulator runs slowly"

- Increase RAM: In Device Manager, click device → Edit → Virtual RAM (4GB+)
- Close other apps
- Disable animations: Settings > Developer options > Turn off animations
- Use API 33/34, not older versions

### "Out of disk space"

```bash
# Delete and recreate emulator
$ANDROID_SDK_ROOT/cmdline-tools/bin/avdmanager delete avd -n Pixel_5

# Recreate it
./setup-android.sh
```

---

## Quick Commands

```bash
# Start emulator
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &

# List devices
adb devices

# Check emulator logs
adb logcat | grep ExpenseSettle

# Stop emulator
adb emu kill

# Unlock emulator
adb shell input keyevent 82

# Install app manually
adb install app.apk

# Clear app data
adb shell pm clear com.expensesettle.app

# Take screenshot
adb shell screencap /sdcard/screenshot.png
adb pull /sdcard/screenshot.png ./screenshot.png
```

---

## What You Should See When App Loads

```
┌────────────────────────────────┐
│   ExpenseSettle                │
├────────────────────────────────┤
│                                │
│  Balance: ₹0                   │
│  Pending: 0                    │
│  Your Groups: 0                │
│                                │
│  [Create Group Button]         │
│                                │
│  [Other content...]            │
│                                │
└────────────────────────────────┘
```

---

## Next Steps

Once emulator is working and app loads:

1. ✅ Test navigation
2. ✅ Test login (if you have login page)
3. ✅ Test creating expense
4. ✅ Try different pages
5. ✅ Check console logs for errors:
   ```bash
   adb logcat | grep -i "expensesettle\|error\|exception"
   ```

---

## Troubleshooting Logs

Check detailed Android logs:

```bash
# Save logs to file
adb logcat > android_logs.txt &

# Stop logging
kill %1

# View logs
cat android_logs.txt | grep -i error
```

---

**Your Android emulator is ready! Next: Run setup script and test.** 🚀
