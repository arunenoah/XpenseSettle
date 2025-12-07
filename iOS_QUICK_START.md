# iOS Development & TestFlight - Quick Start

Get ExpenseSettle running on iOS and deployed to TestFlight for friend testing.

---

## 🚀 Quick Timeline

| Step | Time | Requirement |
|------|------|-------------|
| 1. Wait for Xcode | 15-30 min | Already started |
| 2. Get Apple Developer | 5 min | $99/year ($0 if app stays free) |
| 3. Build app | 10 min | Xcode installed |
| 4. Upload to TestFlight | 5 min | Developer account |
| 5. Invite friends | 2 min | Their Apple IDs |
| **TOTAL** | **~45 min** | |

---

## ✅ Status: What's Done

- ✅ iOS platform added to Capacitor
- ✅ CocoaPods dependencies installed
- ✅ Plugins configured (Camera, Geolocation, Notifications)
- ✅ iOS project structure created
- ✅ Ready to build

---

## ⏳ WAIT: Xcode Installation

Your Xcode is downloading from App Store (~12GB).

**Check progress:**
1. Open App Store
2. Click **"Updates"** tab
3. Look for Xcode download
4. Wait for it to finish (could take 15-30 minutes)

**Once Xcode finishes**, come back and continue below.

---

## Step 1: Create Apple Developer Account

**Cost:** $99/year (or free if your app stays free)

**What you need:**
- Apple ID (your iCloud account)
- Credit card for $99 fee

**Do this:**
1. Go to: **https://developer.apple.com/account/**
2. Sign in with Apple ID
3. Enroll in **"Apple Developer Program"**
4. Accept terms & conditions
5. Complete payment

**Wait for approval** (usually instant, sometimes 24 hours)

---

## Step 2: Create App in App Store Connect

Once developer account is approved:

1. Go to: **https://appstoreconnect.apple.com/**
2. Sign in with Apple ID
3. Click **"My Apps"** (left menu)
4. Click **"+"** button → **"New App"**
5. Fill in:
   - **Platform:** iOS
   - **Name:** ExpenseSettle
   - **Bundle ID:** com.expensesettle.app
   - **SKU:** expensesettle-001
6. Click **"Create"**

Done! Your app is registered.

---

## Step 3: Build the App with Xcode

**Once Xcode finishes installing:**

```bash
# Navigate to project
cd /Users/arunkumar/Documents/Application/expenseSettle

# Open Xcode workspace (use .xcworkspace, NOT .xcodeproj)
open ios/App/App.xcworkspace
```

### In Xcode:

1. **Select Team:**
   - Click **"App"** in left panel
   - Select **"Signing & Capabilities"** tab
   - Click **"Team"** dropdown
   - Select your team

2. **Verify Bundle ID:**
   - Should show: `com.expensesettle.app`

3. **Archive for Release:**
   - At top: Change from **"iPhone Simulator"** to **"Any iOS Device (arm64)"**
   - Click **"Product"** menu → **"Archive"**
   - Wait for build (3-5 minutes)

---

## Step 4: Upload to TestFlight

After archiving:

1. **"Archive Successful"** dialog appears
2. Click **"Distribute App"**
3. Select **"TestFlight"** (then "Next")
4. Select **"Upload"** (then "Next")
5. Xcode will upload automatically
6. Wait for upload to complete

Check upload status:
- Go to: **https://appstoreconnect.apple.com/**
- Select **"My Apps"** → **"ExpenseSettle"**
- Click **"TestFlight"** tab
- Look for your build (processing takes 5-10 min)

---

## Step 5: Invite iOS Friends

Once build is processed:

### For Your First Test (Internal):

1. In App Store Connect, click **"Internal Testing"**
2. Your Apple ID is already there
3. You can test the app immediately!

### For Friends (External Testing):

1. Click **"External Testing"**
2. Click **"Create Test Link"**
3. Enter your iOS friends' email addresses
4. Select the build to share
5. Click **"Send"**

They will get an email with TestFlight link!

---

## How Friends Get the App

### iOS Friend Instructions:

1. **Download TestFlight from App Store**
   - Search: "TestFlight"
   - Tap "Get" then "Install"

2. **Open Email Invite**
   - Open email from you
   - Tap the TestFlight link
   - Or open TestFlight app and tap notification

3. **Accept & Install**
   - Tap **"Accept"**
   - Tap **"Install"**
   - App downloads to their iPhone
   - Done! 🎉

---

## Testing on Your Mac Before TestFlight

Want to test locally first?

```bash
# Run on iOS Simulator
open ios/App/App.xcworkspace

# In Xcode:
# 1. Select "iPhone 16 Pro" from top dropdown
# 2. Click Product → Run
# 3. App launches in simulator
```

---

## Troubleshooting

### "Xcode won't open workspace"

```bash
# Reinstall pods
cd ios/App
rm -rf Pods Podfile.lock
pod install
cd ../..

# Try opening again
open ios/App/App.xcworkspace
```

### "Code signing error"

In Xcode:
1. Click **"App"** in left panel
2. Go to **"Signing & Capabilities"**
3. Ensure your team is selected
4. Click "Try Again" button

### "Archive fails"

```bash
# Clean and rebuild
xcodebuild clean -workspace ios/App/App.xcworkspace -scheme App

# Try archiving again in Xcode
```

### "Friends can't install from TestFlight"

Make sure:
1. They have TestFlight app installed
2. They used the email link you sent
3. They tapped "Accept" in TestFlight
4. Build finished processing (check App Store Connect)

---

## Version Updates

Each time you want to push a new version:

1. Update version in Xcode:
   - Click **"App"**
   - **"Build Settings"**
   - Change **"Version"** (e.g., 1.0.0 → 1.0.1)
   - Change **"Build Number"** (e.g., 1 → 2)

2. Archive again and upload

3. TestFlight auto-processes new build

4. Friends see "New Version Available"

---

## Command Quick Reference

```bash
# Open Xcode
open ios/App/App.xcworkspace

# Install dependencies
cd ios/App && pod install && cd ../..

# Clean build
xcodebuild clean -workspace ios/App/App.xcworkspace -scheme App

# Archive from command line
xcodebuild archive \
  -workspace ios/App/App.xcworkspace \
  -scheme App \
  -configuration Release \
  -derivedDataPath build \
  -archivePath "build/App.xcarchive"

# Check available simulators
xcrun simctl list devices
```

---

## Checklist

Before TestFlight upload:

- ⏳ Waiting for Xcode to finish downloading
- ⬜ Create Apple Developer account
- ⬜ Create app in App Store Connect
- ⬜ Build archive in Xcode
- ⬜ Upload to TestFlight
- ⬜ Process completes (5-10 min)
- ⬜ Invite iOS friends
- ⬜ Friends test on their iPhones
- ⬜ Collect feedback

---

## Next Steps After TestFlight

1. **Gather Feedback** - Friends test and report issues
2. **Fix Bugs** - Make updates and push new builds
3. **App Store Submission** - When ready for public release:
   - Add screenshots
   - Write description
   - Submit for review
   - Apple approves (1-3 days)
   - App goes live! 🎉

---

## Resources

- **App Store Connect:** https://appstoreconnect.apple.com/
- **Developer Account:** https://developer.apple.com/
- **Capacitor iOS Guide:** https://capacitorjs.com/docs/ios
- **TestFlight Help:** https://help.apple.com/testflight/

---

**Once Xcode finishes installing (watch App Store), you'll be ready to build!** 🍎

**Estimated total time to TestFlight: 45 minutes**

---

### Your Folder Structure Now:

```
expenseSettle/
├── ios/                          # iOS Xcode project
│   ├── App/
│   │   ├── App.xcworkspace      # Open THIS in Xcode
│   │   ├── Pods/                # Dependencies
│   │   ├── App.xcodeproj/
│   │   └── ...
│   └── ...
├── android/                       # Android project
├── releases/                      # APK for friends
├── public/                        # Web assets
├── app/                          # Laravel backend
└── ...
```

---

**Ready! Keep watching App Store for Xcode to finish, then start building!** 🚀
