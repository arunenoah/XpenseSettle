#!/bin/bash

# ExpenseSettle APK Installation Script
# For quick installation on connected Android device

echo "╔════════════════════════════════════╗"
echo "║   ExpenseSettle APK Installer     ║"
echo "╚════════════════════════════════════╝"
echo ""

# Check if ADB is available
if ! command -v adb &> /dev/null; then
    echo "❌ ADB is not installed or not in PATH"
    echo ""
    echo "Please install ADB or use manual installation:"
    echo "See README.md for details"
    exit 1
fi

# Check for connected devices
echo "🔍 Checking for connected devices..."
devices=$(adb devices | grep -v "List of attached" | grep device)

if [ -z "$devices" ]; then
    echo "❌ No Android devices found"
    echo ""
    echo "Please:"
    echo "1. Connect your Android phone via USB"
    echo "2. Enable USB Debugging in Developer Options"
    echo "3. Grant USB debugging permission on phone"
    echo "4. Run this script again"
    exit 1
fi

echo "✅ Found device:"
echo "$devices"
echo ""

# Find the APK file
APK_FILE=$(ls -t ExpenseSettle-*.apk 2>/dev/null | head -1)

if [ -z "$APK_FILE" ]; then
    echo "❌ No APK file found!"
    echo "Expected: ExpenseSettle-*.apk"
    exit 1
fi

echo "📦 Installing: $APK_FILE"
echo ""

# Install the APK
adb install "$APK_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo "╔════════════════════════════════════╗"
    echo "║   ✅ Installation Successful!      ║"
    echo "╚════════════════════════════════════╝"
    echo ""
    echo "Next steps:"
    echo "1. Find 'ExpenseSettle' in your app drawer"
    echo "2. Tap to launch the app"
    echo "3. Wait 2-5 seconds for first load"
    echo "4. Your app loads from: https://xpensesettle.on-forge.com/"
    echo ""
else
    echo ""
    echo "❌ Installation failed!"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check device is still connected: adb devices"
    echo "2. Uninstall old version: adb uninstall com.expensesettle.app"
    echo "3. Try again"
    echo ""
    exit 1
fi
