# iOS TestFlight Deployment Guide

Deploy ExpenseSettle to Apple's TestFlight for iOS testing and sharing with friends.

---

## 📋 Prerequisites (What You Need)

- ✅ macOS (you have this)
- ✅ Xcode installed (~15 minutes to install)
- ✅ CocoaPods installed (gem/package manager for iOS)
- ⚠️ **Apple Developer Account** ($99/year) - **YOU NEED THIS**
  - https://developer.apple.com/account/

- ⚠️ **Apple ID** - For App Store Connect
  - Your iCloud account works!

---

## 🎯 What is TestFlight?

TestFlight is Apple's free platform for sharing beta iOS apps. You can:
- Share with up to 100 internal testers (instant)
- Share with up to 10,000 external testers (via email link)
- Collect feedback and crash logs
- Test features before App Store release

---

## ⏱️ Timeline

| Step | Time | Status |
|------|------|--------|
| Setup (you do now) | 15 min | Start now |
| Wait for Xcode | 15-30 min | In progress |
| Create Apple Developer account | 10 min | Do later |
| Build iOS app | 10 min | After Xcode |
| Upload to TestFlight | 5 min | After build |
| Invite testers | 2 min | Final step |
| **TOTAL** | **~60 minutes** | |

---

## Step 1: Check Prerequisites ✅

```bash
# Verify CocoaPods
pod --version
# Should show: 1.x.x

# Xcode will be installed from App Store
# Watch for download to finish
```

---

## Step 2: Create Apple Developer Account

### If You Don't Have One:

1. Go to: **https://developer.apple.com/account/**
2. Sign in with your Apple ID (iCloud account)
3. Agree to terms
4. If needed, upgrade to Developer Program ($99/year)
5. **Wait for approval** (usually instant, sometimes 24 hours)

### If You Already Have One:

Skip to Step 3.

---

## Step 3: Create Your App in App Store Connect

Once you have developer account:

1. Go to: **https://appstoreconnect.apple.com/**
2. Sign in with Apple ID
3. Click **"My Apps"** (top left)
4. Click **"+"** → **"New App"**
5. Fill in:
   - **Platform:** iOS
   - **Name:** ExpenseSettle
   - **Bundle ID:** com.expensesettle.app
   - **SKU:** expensesettle-mobile
   - **Category:** Productivity or Finance
6. Click **"Create"**

---

## Step 4: Configure Xcode Project

Once Xcode is installed (watch for App Store to finish):

```bash
# Navigate to project
cd /Users/arunkumar/Documents/Application/expenseSettle

# Install iOS dependencies
cd ios/App
pod install
cd ../../

# Open Xcode
open ios/App/App.xcworkspace
```

### In Xcode:

1. **Select Signing & Capabilities Tab:**
   - Click on "App" → "Signing & Capabilities"

2. **Configure Team:**
   - Select your team from the "Team" dropdown
   - Xcode will auto-configure signing

3. **Verify Bundle ID:**
   - Should be: `com.expensesettle.app`

4. **Check Version:**
   - Go to Build Settings
   - Set Version: 1.0.0
   - Build Number: 1

---

## Step 5: Build the iOS App

### Option A: Using Xcode GUI (Easiest)

1. **In Xcode**, select **"Any iOS Device (arm64)"** from dropdown (top)
2. Click **"Product"** → **"Archive"**
3. Wait for build (3-5 minutes)
4. When complete, **"Distribute App"** dialog opens
5. Select **"TestFlight"**
6. Complete the wizard

### Option B: Using Command Line

```bash
cd ios/App

# Build archive
xcodebuild -workspace App.xcworkspace \
  -scheme App \
  -configuration Release \
  -derivedDataPath build \
  archive -archivePath "build/App.xcarchive"

# This creates a build ready to upload
```

---

## Step 6: Upload to TestFlight

### Method A: From Xcode (Automatic)

After archiving in Xcode:
1. Click **"Distribute App"**
2. Select **"TestFlight"**
3. Select **"Upload"**
4. Select your team
5. Xcode uploads automatically ✅

### Method B: Using Transporter (Alternative)

```bash
# Download Transporter from App Store
# Or use command line:
xcrun altool --upload-app \
  --type ios \
  --file "ios/App/build/App.ipa" \
  --username "your-apple-id@icloud.com" \
  --password "your-app-specific-password"
```

---

## Step 7: Process on App Store Connect

After upload:

1. Go to **https://appstoreconnect.apple.com/**
2. Select **"My Apps"** → **"ExpenseSettle"**
3. Click **"TestFlight"** tab (left menu)
4. Wait for processing (usually 5-10 minutes)
5. App appears under **"Builds"** section

---

## Step 8: Invite Testers

### Internal Testers (Instant):

1. In App Store Connect, go **"TestFlight"** tab
2. Click **"Internal Testing"** section
3. Click **"+"** to add testers
4. Add your Apple ID (usually you)
5. They get notification immediately

### External Testers (Via Email):

1. In App Store Connect, **"TestFlight"** tab
2. Click **"External Testing"** section
3. Click **"Create New Test Link"**
4. Enter tester emails (your iOS friends)
5. They receive email link to join
6. They click link and install via TestFlight app

---

## How Friends Install the App

### For Your iOS Friends:

1. **Download TestFlight App** from App Store
   - Search for "TestFlight" and install

2. **Accept Invitation:**
   - Get email from you with TestFlight link
   - Open email on iPhone
   - Tap link or open TestFlight
   - Tap "Accept" and "Install"

3. **Wait for Install:**
   - App downloads (~5 minutes)
   - Tap "Open" when ready

4. **Test the App:**
   - Opens to your production website
   - All features work
   - Can send feedback in TestFlight

---

## Managing TestFlight Versions

### Update App Version:

Every time you build a new version:

```bash
# Increment version in Xcode or:
# Edit: ios/App/App.xcodeproj/project.pbxproj
# Change: MARKETING_VERSION = "1.0.0"
# Change: CURRENT_PROJECT_VERSION = "1"
```

Then archive again and upload.

### TestFlight Auto-Expires:

- Each build expires after 90 days
- Plan updates accordingly

---

## Files Created for iOS

```
ios/
├── App/
│   ├── App.xcodeproj/          # Xcode project
│   ├── App/                    # App code
│   ├── Pods/                   # Cocoapods dependencies
│   ├── App.xcworkspace         # Workspace (use this in Xcode)
│   └── Podfile                 # iOS dependencies
└── ...
```

---

## Common Issues & Solutions

### "Team ID not found"

```bash
# Make sure you have developer account
# Check at: developer.apple.com/account
# Wait for approval if just created
```

### "Bundle ID is registered"

If error says bundle ID already used:
1. Go to App Store Connect
2. Select existing app
3. Continue with that ID

### "Code signing error"

```bash
# Ensure Xcode has your team selected
# In Xcode: App target → Signing & Capabilities
# Select your team from dropdown
```

### "Build fails with framework error"

```bash
# Clean and rebuild
cd ios/App
rm -rf Pods
pod install
cd ../..
# Try building again
```

### "App expires in TestFlight"

Each build expires in 90 days. Just upload a new build before it expires.

---

## Checklist Before Uploading

- ✅ Xcode installed and opens
- ✅ CocoaPods installed
- ✅ Apple Developer account created
- ✅ App created in App Store Connect
- ✅ Bundle ID matches: `com.expensesettle.app`
- ✅ Version set correctly
- ✅ Team selected in Xcode
- ✅ Signing certificates valid

---

## TestFlight Testing Checklist

Once friends have the app, have them test:

- ✅ App launches without crash
- ✅ Website loads (https://xpensesettle.on-forge.com/)
- ✅ Can log in with existing account
- ✅ Dashboard displays correctly
- ✅ Can create groups
- ✅ Can add expenses
- ✅ Can settle payments
- ✅ Can view balance/pending
- ✅ No blank screens or errors
- ✅ Performance is acceptable
- ✅ Notifications work (if enabled)

---

## Sharing Feedback with Testers

In TestFlight:
- Testers tap **"Send Feedback"** in app
- Testers file "Test Flight Feedback"
- Crashes auto-reported with logs
- You see all feedback in App Store Connect

---

## Next: App Store Release

Once testing is complete:

1. Update app metadata in App Store Connect
2. Add screenshots (required)
3. Write description
4. Submit for App Store review
5. Apple reviews (1-3 days)
6. App goes live on App Store!

---

## Resources

- **App Store Connect:** https://appstoreconnect.apple.com/
- **Developer Account:** https://developer.apple.com/account/
- **Capacitor iOS Docs:** https://capacitorjs.com/docs/ios
- **Xcode Help:** In Xcode menu → Help
- **TestFlight Help:** https://help.apple.com/testflight/

---

## Summary

You're now ready for:

1. ⏳ Wait for Xcode to install (15-30 min)
2. 🎫 Create Apple Developer account ($99/year)
3. 🔧 Setup Xcode and sign code
4. 📦 Build iOS app
5. 🚀 Upload to TestFlight
6. 👥 Invite iOS friends
7. 📱 They test on their iPhones

**Continue below once Xcode finishes installing!**

---

## Quick Command Reference

```bash
# Check tools are installed
pod --version
xcodebuild -version

# Open Xcode project
open ios/App/App.xcworkspace

# Build from command line
xcodebuild -workspace ios/App/App.xcworkspace \
  -scheme App \
  -configuration Release \
  -derivedDataPath build \
  archive -archivePath "build/App.xcarchive"

# View available simulators
xcrun simctl list devices available

# Run on simulator
xcrun simctl install booted "build/App.app"
```

---

**Ready to build for iOS? Let me know once Xcode finishes installing!** 🍎
