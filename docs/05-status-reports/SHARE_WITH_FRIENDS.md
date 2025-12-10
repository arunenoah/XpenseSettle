# Share ExpenseSettle with Friends - Complete Guide

Share your app with Android and iOS friends for testing.

---

## 📱 For Android Friends (APK)

### **Easiest Way: Direct File Share**

Your Android APK is ready in: `releases/ExpenseSettle-v1.0.0-debug.apk`

**Option 1: Cloud Share**
```
1. Upload releases/ folder to Google Drive
2. Share link with Android friends
3. They download the APK on their phone
4. Open Downloads folder
5. Tap APK to install
```

**Option 2: Email**
```
1. Email the APK file to Android friends
2. They download on phone
3. Tap to install
4. Follow on-screen prompts
```

**Option 3: USB + ADB (For Computer Users)**
```bash
# If they have a Mac/Windows:
cd releases
./INSTALL.sh
# (Or: adb install ExpenseSettle-v1.0.0-debug.apk)
```

### What Android Friends See:

```
1. Download APK
2. Install on phone (Settings → Allow unknown sources if needed)
3. Open "ExpenseSettle" app
4. Website loads: https://xpensesettle.on-forge.com/
5. All features work! 🎉
```

### File in releases/ Folder:
- `ExpenseSettle-v1.0.0-debug.apk` - The app
- `QUICK_INSTALL.txt` - Installation guide
- `README.md` - Detailed instructions
- `INSTALL.sh` - Automated installer

---

## 🍎 For iOS Friends (TestFlight)

### **Setup Timeline**

1. **Wait for Xcode** ⏳ (15-30 min in progress)
2. **Create Apple Developer Account** (10 min, $99/year)
3. **Create App in App Store Connect** (5 min)
4. **Build in Xcode** (10 min)
5. **Upload to TestFlight** (5 min)
6. **Invite Friends** (2 min)
7. **Friends Install** (automatic via TestFlight)

**Total Time: ~60 minutes**

### Step-by-Step

#### Step 1: Wait for Xcode

- Check App Store for Xcode download progress
- It's a large download (~12GB)
- Takes 15-30 minutes depending on internet

#### Step 2: Create Apple Developer Account

1. Go to: **https://developer.apple.com/account/**
2. Sign in with Apple ID (your iCloud account)
3. Enroll in **"Apple Developer Program"** - $99/year
4. Wait for approval (usually instant)

#### Step 3: Create App in App Store Connect

1. Go to: **https://appstoreconnect.apple.com/**
2. Click **"My Apps"** → **"+"** → **"New App"**
3. Fill in:
   - **Name:** ExpenseSettle
   - **Bundle ID:** com.expensesettle.app
   - **SKU:** expensesettle-001
4. Click **"Create"**

#### Step 4: Build the App

```bash
# Open Xcode project
open ios/App/App.xcworkspace

# In Xcode:
# 1. Click "App" (left panel)
# 2. Go to "Signing & Capabilities"
# 3. Select your team
# 4. Change to "Any iOS Device (arm64)" (top dropdown)
# 5. Product → Archive
# 6. Wait 3-5 minutes
```

#### Step 5: Upload to TestFlight

After archiving completes:
1. Click **"Distribute App"**
2. Select **"TestFlight"** → **"Upload"**
3. Xcode uploads automatically
4. Check progress in App Store Connect

#### Step 6: Invite iOS Friends

Once build finishes processing (5-10 min):

1. Go to: **https://appstoreconnect.apple.com/**
2. Select **"ExpenseSettle"** app
3. Click **"TestFlight"** tab
4. Click **"External Testing"**
5. Click **"Create Test Link"**
6. Enter friends' email addresses
7. Send invites

#### Step 7: Friends Install

Your iOS friends will:
1. Receive email from App Store Connect
2. Tap the TestFlight link
3. Install TestFlight app if needed
4. Accept and install ExpenseSettle
5. App appears on home screen
6. Open and test! 🎉

---

## 📋 Distribution Comparison

| Platform | Method | Testers | Time | Cost |
|----------|--------|---------|------|------|
| **Android** | Direct APK | Unlimited | Instant | Free |
| **iOS** | TestFlight | 100 internal, 10k external | 60 min | $99/year |

---

## 📊 What Friends Test

After installation, they should test:

### Essential Features:
- ✅ App launches without crash
- ✅ Website loads properly
- ✅ Login works
- ✅ Dashboard displays

### Core Features:
- ✅ Create groups
- ✅ Add expenses
- ✅ View balances
- ✅ Settle payments
- ✅ Navigation between pages

### Technical:
- ✅ No blank screens
- ✅ Buttons responsive
- ✅ Scrolling smooth
- ✅ No error messages
- ✅ Works on WiFi and mobile data

---

## 💬 Collecting Feedback

### From Android Friends:

Android APK doesn't have built-in feedback, so:
1. Ask them to email you notes
2. Or use a simple feedback form
3. Screenshot issues they encounter

### From iOS Friends:

In TestFlight:
1. They tap **"Send Feedback"** while testing
2. All crashes auto-reported
3. You see feedback in App Store Connect
4. Much easier than Android!

---

## 🔄 Updating the App

### When You Make Changes:

**Android:**
```bash
# Rebuild APK
export JAVA_HOME=/opt/homebrew/opt/openjdk@21
cd android
./gradlew assembleDebug
cd ..

# Copy to releases with new version
cp android/app/build/outputs/apk/debug/app-debug.apk \
   releases/ExpenseSettle-v1.1.0-debug.apk

# Share new version with friends
```

**iOS:**
1. Update version in Xcode
2. Archive again
3. Upload to TestFlight
4. Friends see "New Version Available"
5. They install with one tap!

---

## ❓ Troubleshooting

### Android Friends Can't Install

```
Issue: "Installation blocked"
Solution:
- Settings > Apps > Special app access > Install unknown apps
- Toggle ON file manager
- Try again
```

### iOS Friends Don't Get Email

```
Issue: No TestFlight invite email
Solution:
- Check spam folder
- Ensure correct email address
- Resend invite in App Store Connect
```

### App Shows Blank Screen

```
Issue: White/blank screen on any platform
Solution:
- Check internet connection on their phone
- Try opening Chrome → https://xpensesettle.on-forge.com/
- If website loads, app should work too
- Restart app
```

---

## Security Notes for Friends

Your app is running **production code** - the actual live app!

Make sure friends know:
- ✅ Safe to test (same as live website)
- ✅ Their data is real (same database)
- ✅ Share only with trusted testers
- ✅ Beta builds expire (Android after manual update, iOS after 90 days)

---

## Next: Official Release

Once testing is complete and feedback addressed:

**For Android:**
- Build release APK
- Sign with release keystore
- Submit to Google Play Store
- 2-4 hour review, then live!

**For iOS:**
- Update screenshots and description
- Submit to App Store for review
- 1-3 day review, then live!

See: DEPLOY_TO_APP_STORES.md

---

## Quick Links

| File/Link | Purpose |
|-----------|---------|
| `releases/` | Android APK & guides |
| `iOS_QUICK_START.md` | iOS setup (read this) |
| `iOS_TESTFLIGHT_SETUP.md` | Detailed iOS guide |
| `APK_BUILD_QUICK_GUIDE.md` | Android detailed guide |
| https://appstoreconnect.apple.com/ | Manage iOS testers |
| https://developer.apple.com/ | Developer account |

---

## Summary

### Android (Easiest):
1. ✅ APK built and ready
2. Share `releases/ExpenseSettle-v1.0.0-debug.apk`
3. Android friends install directly
4. Done! 🎉

### iOS (Requires Developer Account):
1. ⏳ Wait for Xcode to finish
2. Create Apple Developer account ($99/year)
3. Build and upload to TestFlight
4. Invite friends via email
5. They install via TestFlight app
6. Done! 🍎

**Both approaches are free to test, low friction for friends!**

---

**Ready to share?** Pick a platform and get started! 🚀
