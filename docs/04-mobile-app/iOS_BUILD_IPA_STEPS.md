# Build iOS IPA for AltStore Distribution

Step-by-step visual guide for building native iOS app.

---

## 📋 What You'll Do

1. Configure Xcode (5 min)
2. Build Archive (10 min)
3. Export IPA (5 min)
4. Upload to Google Drive (2 min)
5. Share with friends (instant)

**Total Time: 30 minutes**

---

## Step 1: Xcode is Opening

Xcode should be opening now. Wait for it to fully load (2-3 minutes).

You'll see the Capacitor iOS project with:
- Left panel: Project files
- Center: Code editor
- Right panel: Inspector

---

## Step 2: Configure Free Apple ID Signing

### 2a. Add Your Apple Account

1. **Click "Xcode" menu** (top left corner)
2. **Click "Preferences..."**
3. **Click "Accounts" tab**
4. **Click "+" button** (bottom left)
5. **Select "Apple ID"**
6. **Sign in with your Apple ID:**
   - Email: your Apple ID (iCloud)
   - Password: your Apple password
   - Click "Sign In"
7. **Wait for it to finish**
8. **Close Preferences** (Command+, or click red X)

### 2b. Configure Signing for the App

1. **In left panel, click "App"** (blue folder with white 'A')
2. **Click "Signing & Capabilities" tab** (near top)
3. **Look for "Signing" section:**
   - Check the box: **"Automatically manage signing"**
   - For "Team" dropdown: **Select your name** (should appear now)
4. **Verify Bundle ID:**
   - Should show: `com.expensesettle.app`

✅ Signing is now configured!

---

## Step 3: Build the Archive

### 3a. Select Device

1. **At the top of Xcode, look for device dropdown**
2. **Current shows something like "iPhone 16 Pro"**
3. **Click the dropdown**
4. **Select "Any iOS Device (arm64)"**

This is important! It builds for real devices, not simulator.

### 3b. Archive the App

1. **Click "Product" menu** (top menu bar)
2. **Click "Archive"**
3. **Wait for build** (3-5 minutes)

You'll see build progress at the bottom:
- "Compiling..."
- "Linking..."
- "Processing..."
- Finally: "Archive Successful!"

A window will pop up showing "Archive".

✅ Archive is complete!

---

## Step 4: Export as IPA

After archiving completes, a dialog appears with your archive.

### 4a. Choose Export Method

1. **Click "Distribute App"** button
2. **Select "Custom"** (this lets us export IPA)
3. **Click "Next"**

### 4b. Configure Export

1. **Select "Export IPA"** option
2. **Click "Next"**
3. **Select options:**
   - App Thinning: "Automatic"
   - Keep defaults for everything else
4. **Click "Next"**
5. **Choose save location:**
   - Select Desktop or Documents
   - Name: `ExpenseSettle.ipa`
6. **Click "Export"**

### 4c. Wait for Export

Xcode will:
- Process the app
- Create the IPA file
- Ask for code signing
- Finally save the IPA

When done, you'll see: **"The archive was successfully exported"**

The `ExpenseSettle.ipa` file is now on your computer! 🎉

**File size:** ~30-50 MB

---

## Step 5: Upload to Google Drive

### 5a. Go to Google Drive

1. Open browser: **drive.google.com**
2. Sign in with your Google account
3. Click **"New"** button (left)
4. Click **"Folder Upload"**

### 5b. Select and Upload IPA

1. Navigate to where you saved the IPA
2. Select `ExpenseSettle.ipa`
3. Wait for upload (2-3 minutes)

### 5c. Create Shareable Link

1. Right-click the `ExpenseSettle.ipa` file in Google Drive
2. Click **"Share"**
3. Click **"Change"** (on the "Restricted" button)
4. Change to **"Anyone with the link"**
5. Click **"Copy link"**

You now have a shareable link! 📤

---

## Step 6: Share with iOS Friends

Send your iOS friends this message:

```
Hey! 🎉 I built an iOS app for ExpenseSettle!

To install:

1. Download AltStore:
   Go to altstore.io on your iPhone
   Download the AltStore app

2. Get my app:
   Download ExpenseSettle.ipa from:
   [PASTE YOUR GOOGLE DRIVE LINK HERE]

3. Install in AltStore:
   Open AltStore
   Tap the "+" button in Apps tab
   Select the ExpenseSettle.ipa file
   When asked, enter your Apple ID password
   Tap Install

4. Done!
   The app will appear on your home screen
   You can open it like any app

Questions? Let me know!
```

---

## Summary

✅ Step 1: Build web assets - DONE
✅ Step 2: Configure Xcode signing - DO NOW
✅ Step 3: Build archive - DO NOW
✅ Step 4: Export IPA - DO NOW
⬜ Step 5: Upload to Google Drive - AFTER IPA
⬜ Step 6: Share with friends - AFTER UPLOAD

---

## Troubleshooting

### "Signing error"

**Error:** "Code signing error" or "Team not available"

**Solution:**
1. Go back to Preferences → Accounts
2. Make sure your Apple ID appears
3. Try selecting your team again in Signing & Capabilities

### "Archive failed"

**Error:** Build fails before archiving

**Solution:**
1. Click **"Product"** → **"Clean Build Folder"**
2. Wait for clean to complete
3. Try **"Product"** → **"Archive"** again

### "IPA export failed"

**Error:** "Export failed" or "Code signing error"

**Solution:**
1. Make sure you selected "Custom"
2. Make sure you selected "Export IPA"
3. Try again with defaults

---

## What's Happening

When you build an IPA:

1. **Compilation:** Xcode compiles your app code
2. **Linking:** Links all dependencies
3. **Code Signing:** Signs with your Apple ID (security)
4. **Packaging:** Creates the IPA file
5. **Export:** Saves as portable package

---

## Next: Friends Install with AltStore

Once friends have the IPA:

1. **They download AltStore** (free app from altstore.io)
2. **They download your IPA** from Google Drive link
3. **They open AltStore** and tap "+"
4. **They select your IPA**
5. **They enter Apple ID password**
6. **AltStore installs it** to their home screen! ✅

---

## Important Notes

- ✅ Your Apple ID is FREE (no $99 fee)
- ✅ Code signing uses free development team
- ✅ IPA works for unlimited friends
- ⏰ App refreshes every 7 days (friends just tap refresh in AltStore - 30 sec)
- 🔄 Easy updates (build new IPA, friends reinstall)

---

**Now go to Xcode and follow the steps above!** 🍎
