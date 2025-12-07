# ExpenseSettle - Android APK Releases

Download and install the ExpenseSettle app on your Android phone.

---

## 📱 Installation Instructions

### Option 1: Using ADB (Computer Required - Fastest)

**Prerequisites:**
- Android phone with USB Debugging enabled
- USB cable
- ADB installed on your computer

**Steps:**

1. **Connect your Android phone** to computer via USB
2. **On your phone**, enable USB Debugging:
   - Settings → Developer Options → USB Debugging (turn ON)
   - Grant USB debugging permission when prompted
3. **On your computer**, run:
   ```bash
   adb devices  # Verify phone shows up
   adb install ExpenseSettle-v1.0.0-debug.apk
   ```
4. **Success!** App is installed on your phone

---

### Option 2: Manual Installation (No Computer Required)

**Prerequisites:**
- Android phone
- File transfer cable or cloud storage (Google Drive, Dropbox, etc.)

**Steps:**

1. **Transfer APK file** to your phone:
   - Via USB cable: Copy APK to phone's Downloads folder
   - Via cloud: Upload APK to Google Drive/Dropbox and download on phone

2. **On your phone**:
   - Open **Downloads** or your file manager
   - Find **ExpenseSettle-v1.0.0-debug.apk**
   - Tap to install
   - If prompted: **Allow installation from unknown sources**
     - Settings → Apps → Special app access → Install unknown apps
     - Enable for your file manager

3. **Tap "Install"** and wait for installation to complete

4. **Done!** App is installed

---

## 🚀 Launch the App

1. Open **App Drawer** (swipe up from home screen)
2. Find **ExpenseSettle**
3. Tap to open
4. Wait 2-5 seconds for first load
5. Your production app loads from: https://xpensesettle.on-forge.com/

---

## ✅ Test Checklist

After launching, verify:

- ✅ App loads without errors or blank screen
- ✅ Dashboard displays with your data
- ✅ Can see Balance, Pending Payments, Groups
- ✅ Can scroll through the page
- ✅ Buttons are clickable
- ✅ Can navigate between pages
- ✅ No error messages or crashes
- ✅ Internet works (WiFi or mobile data)

---

## 🐛 Troubleshooting

### "Installation blocked - unknown sources"

**For ADB:**
```bash
adb install ExpenseSettle-v1.0.0-debug.apk
```

**For manual install:**
1. Go to: Settings → Apps → Special app access
2. Find: "Install unknown apps"
3. Select your file manager (Files, Google Drive, etc.)
4. Toggle **ON**
5. Try installing again

### "App crashes or won't load"

1. Check internet connection (open Chrome, visit any website)
2. Force stop and clear app:
   ```bash
   adb shell am force-stop com.expensesettle.app
   adb shell pm clear com.expensesettle.app
   ```
3. Uninstall and reinstall the APK

### "Permission errors"

1. Settings → Apps → ExpenseSettle
2. Tap "Permissions"
3. Grant any requested permissions

### "Blank white screen"

This usually means the app loaded but the website isn't accessible:
1. Check internet connection on phone
2. Try opening https://xpensesettle.on-forge.com in Chrome
3. If website doesn't load, check your internet/firewall

---

## 📋 App Details

- **App Name:** ExpenseSettle
- **Version:** 1.0.0 (Debug)
- **Type:** Expense Sharing App
- **Platform:** Android
- **Web URL:** https://xpensesettle.on-forge.com/
- **App ID:** com.expensesettle.app
- **File Size:** ~7 MB

---

## 🔄 Multiple Versions

All APK versions are stored in this folder. Version numbering:
- `v1.0.0` - Initial release
- `v1.0.1` - Bug fixes
- `v1.1.0` - New features
- etc.

---

## 📞 Support

Having issues?

1. Check **Troubleshooting** section above
2. Verify your internet connection works
3. Try uninstalling and reinstalling
4. Check phone storage has at least 100 MB free

---

## 🎯 Next Steps

After testing on your phone:

1. ✅ Test all features (create expenses, view groups, settle payments)
2. ✅ Check performance (is it fast enough?)
3. ✅ Try on different networks (WiFi vs mobile data)
4. ✅ Share feedback with the developer

---

**Ready to install?** Start with your preferred installation method above! 🚀

---

## 🙏 Thanks

This Android app and deployment documentation was built with the help of **[Claude Code](https://claude.com/claude-code)** - Anthropic's AI assistant for software engineering.

🤖 Generated with Claude Code
