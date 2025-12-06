# Android Development Setup for ExpenseSettle

This guide will get you ready for Android app development.

---

## Prerequisites Installation

### 1. Install Java Development Kit (JDK)

Java is required to build Android apps.

#### Option A: Using Homebrew (Recommended for Mac)

```bash
# Install Homebrew if you don't have it
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install Java (OpenJDK 11)
brew install openjdk@11

# Set Java home
echo 'export PATH="/usr/local/opt/openjdk@11/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Verify installation
java -version
```

#### Option B: Download from Oracle
1. Go to https://www.oracle.com/java/technologies/downloads/
2. Download JDK 11+ for macOS
3. Run installer
4. Verify: `java -version`

### 2. Install Android Studio

Android Studio is the IDE for Android development.

#### Download and Install

1. Go to https://developer.android.com/studio
2. Click "Download Android Studio"
3. Select macOS version
4. Run installer and follow setup wizard
5. In setup wizard, ensure:
   - ✅ Android SDK
   - ✅ Android SDK Platform-Tools
   - ✅ Android SDK Build-Tools
   - ✅ Android Emulator

#### Post-Installation Setup

After Android Studio installs, set environment variables:

```bash
# Add to ~/.zshrc (or ~/.bash_profile for bash)
echo 'export ANDROID_SDK_ROOT=$HOME/Library/Android/sdk' >> ~/.zshrc
echo 'export PATH=$ANDROID_SDK_ROOT/platform-tools:$PATH' >> ~/.zshrc
echo 'export PATH=$ANDROID_SDK_ROOT/tools:$PATH' >> ~/.zshrc

# Reload shell
source ~/.zshrc

# Verify
adb --version
```

### 3. Install Android SDK Components

```bash
# Update SDK
$ANDROID_SDK_ROOT/cmdline-tools/bin/sdkmanager --sdk_root=$ANDROID_SDK_ROOT "platform-tools"
$ANDROID_SDK_ROOT/cmdline-tools/bin/sdkmanager --sdk_root=$ANDROID_SDK_ROOT "platforms;android-34"
$ANDROID_SDK_ROOT/cmdline-tools/bin/sdkmanager --sdk_root=$ANDROID_SDK_ROOT "build-tools;34.0.0"
$ANDROID_SDK_ROOT/cmdline-tools/bin/sdkmanager --sdk_root=$ANDROID_SDK_ROOT "emulator"
```

### 4. Create Android Virtual Device (Emulator)

```bash
# Launch Android Studio
open -a "Android Studio"

# In Android Studio:
# 1. Click "Tools" → "Device Manager"
# 2. Click "Create Device"
# 3. Select "Pixel 5" (good test device)
# 4. Choose Android 13+ (API 33+)
# 5. Click "Finish"
```

Or use command line:

```bash
# Create emulator
$ANDROID_SDK_ROOT/cmdline-tools/bin/avdmanager create avd \
  -n Pixel_5 \
  -k "system-images;android-34;google_apis;arm64-v8a" \
  -d "Pixel 5"

# Start emulator
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5
```

---

## Verify Setup

Run these commands to verify everything is installed:

```bash
# Check Java
java -version
# Should show: openjdk version "11.x.x" or higher

# Check Android SDK
ls $ANDROID_SDK_ROOT
# Should show: emulator, platforms, tools, platform-tools

# Check adb (Android Debug Bridge)
adb --version
# Should show version

# Check sdkmanager
$ANDROID_SDK_ROOT/cmdline-tools/bin/sdkmanager --list | head -20
```

---

## Troubleshooting Installation

### "Java not found"
```bash
# Make sure Java home is set
echo $JAVA_HOME

# If empty, set it explicitly in ~/.zshrc
export JAVA_HOME=$(/usr/libexec/java_home)
export PATH=$JAVA_HOME/bin:$PATH
```

### "ANDROID_SDK_ROOT not found"
```bash
# Set it in ~/.zshrc
export ANDROID_SDK_ROOT=$HOME/Library/Android/sdk
source ~/.zshrc
```

### "gradle: command not found"
- Android Studio should provide Gradle
- Or it will be provided by Capacitor setup

### Emulator won't start
```bash
# Start emulator from command line instead
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &

# Or delete and recreate virtual device
rm -rf ~/.android/avd/Pixel_5.avd
# Create new one using Android Studio
```

---

## Next Steps

Once installation is complete:

1. ✅ Run Capacitor setup: `./setup-mobile.sh`
2. ✅ Start Android emulator: `$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &`
3. ✅ Build web app: `npm run build`
4. ✅ Test on Android: `npx cap run android`

---

## Quick Commands Reference

```bash
# Start Android emulator
$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &

# List all virtual devices
$ANDROID_SDK_ROOT/cmdline-tools/bin/avdmanager list avd

# Open Android Studio
open -a "Android Studio"

# Open project in Android Studio
open -a "Android Studio" android/

# Check connected devices
adb devices

# View device logs
adb logcat | grep -i expensesettle

# Stop emulator
adb emu kill
```

---

## Uninstall/Reinstall

If you need to start fresh:

```bash
# Remove Android Studio
rm -rf /Applications/Android\ Studio.app

# Remove Android SDK
rm -rf ~/Library/Android/sdk

# Download and reinstall from https://developer.android.com/studio
```

---

**Setup is ready! Follow the main Capacitor guide next.** 🚀
