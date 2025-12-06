# Deploy ExpenseSettle to App Store & Google Play

This guide walks you through submitting your Capacitor app to the iOS App Store and Google Play Store.

---

## Prerequisites

### For iOS
- **Mac computer** (required for iOS builds)
- **Xcode 14+** installed
- **Apple Developer Account** ($99/year)
- **App ID** in Apple Developer
- **Provisioning Profile**
- **Distribution Certificate**

### For Android
- **Android Studio** installed
- **Java JDK 11+**
- **Google Play Developer Account** ($25 one-time)
- **Signing Keystore**

---

## Part 1: Deploy to iOS App Store

### Step 1: Create App ID in Apple Developer

1. Go to https://developer.apple.com/account
2. Log in with your Apple ID
3. Go to "Identifiers" → "App IDs"
4. Click "+" to create new App ID
5. Enter:
   - **App Type**: App
   - **Description**: ExpenseSettle
   - **Bundle ID**: com.expensesettle.app
6. Click "Continue" → "Register"

### Step 2: Create Distribution Certificate

1. In Apple Developer, go to "Certificates, Identifiers & Profiles"
2. Click "Certificates"
3. Click "+" to create new certificate
4. Select "App Store and Ad Hoc"
5. Click "Continue"
6. Upload your Certificate Signing Request:
   ```bash
   # On Mac, open Keychain Access
   # Go to: Keychain Access > Certificate Assistant > Request a Certificate from a Certificate Authority
   # Save as: CertificateSigningRequest.certSigningRequest
   ```
7. Download the certificate and add to Keychain

### Step 3: Create Provisioning Profile

1. In Apple Developer, click "Provisioning Profiles"
2. Click "+" to create new profile
3. Select "App Store"
4. Select your App ID
5. Select your certificate
6. Name: "ExpenseSettle App Store"
7. Download and open in Xcode

### Step 4: Configure Xcode for Release

Open your project in Xcode:

```bash
open ios/App/App.xcworkspace
```

Configure:

1. **General Tab**:
   - Bundle Identifier: `com.expensesettle.app`
   - Version: `1.0.0`
   - Build: `1`
   - Minimum Deployment: iOS 12.0+

2. **Signing & Capabilities**:
   - Team: Your Apple Developer Team
   - Provisioning Profile: ExpenseSettle App Store
   - Code Sign Identity: Distribution

3. **Build Settings**:
   - Search "Code Sign Style"
   - Set to "Automatic"

4. **Product > Archive**:
   - Select "Any iOS Device (arm64)"
   - Click Product > Archive
   - Wait for build to complete

### Step 5: Upload to App Store

After Archive completes:

1. **Xcode Organizer** appears automatically
2. Click "Distribute App"
3. Select "App Store Connect"
4. Select "Upload"
5. Select your signing options
6. Review and upload

### Step 6: Submit to App Store

1. Go to https://appstoreconnect.apple.com
2. Click "My Apps"
3. Select "ExpenseSettle"
4. Fill in:
   - **App Name**: ExpenseSettle
   - **Subtitle**: Split Expenses Easily
   - **Description**: Track and split expenses with friends effortlessly
   - **Keywords**: expense, split, payment, tracker
   - **Support URL**: https://yourdomain.com/support
   - **Privacy Policy URL**: https://yourdomain.com/privacy

5. **Screenshots** (Required):
   - Provide 5-8 screenshots for each device size
   - Focus on key features (dashboard, expenses, payments)
   - Use App Preview builder for demos

6. **Preview**:
   - Add 30-second video (optional but recommended)
   - Shows app in action

7. **General Information**:
   - Age Rating: 4+
   - Alcohol/Tobacco/Drugs: No
   - Gambling: No

8. **App Pricing and Availability**:
   - Price: Free
   - Available in all countries

9. **Build**:
   - Select your uploaded build

10. **App Review Information**:
    - Demo Account (if required):
      - Email: test@example.com
      - Password: testpass
    - Notes: Simple expense tracking app
    - Contact: Your email
    - Phone: Your phone

11. **Submit for Review**:
    - Review all information
    - Click "Submit for Review"

**Typical Review Time**: 24-48 hours

---

## Part 2: Deploy to Google Play Store

### Step 1: Create Signing Keystore

```bash
# Generate keystore for signing Android app
keytool -genkey -v -keystore ~/expensesettle.keystore \
    -keyalg RSA -keysize 2048 -validity 10000 \
    -alias expensesettle

# When prompted:
# - Keystore password: [create strong password]
# - Key password: [same as keystore password]
# - First and last name: Your Name
# - Organizational unit: ExpenseSettle
# - Organization: Your Company
# - City: Your City
# - State: Your State
# - Country: US
```

Save keystore password safely (you'll need it for future releases).

### Step 2: Create Google Play App

1. Go to https://play.google.com/console
2. Create new project
3. Click "Create App"
4. Enter:
   - **App Name**: ExpenseSettle
   - **Default Language**: English
   - **App Category**: Finance

5. Complete app details:
   - **App Type**: Application
   - **Pricing**: Free
   - **Target Audience**: 3+ (Unrated, for financial apps)

### Step 3: Configure App in Android Studio

File: `android/app/build.gradle`

```gradle
android {
    // ... existing config ...

    signingConfigs {
        release {
            keyAlias 'expensesettle'
            keyPassword '***YOUR_PASSWORD***'
            storeFile file('/Users/yourname/expensesettle.keystore')
            storePassword '***YOUR_PASSWORD***'
        }
    }

    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }

    // Increment version for each release
    versionCode 1
    versionName "1.0.0"
}
```

### Step 4: Build Release APK/AAB

```bash
# Build release app bundle (recommended for Play Store)
cd android
./gradlew bundleRelease

# Output: android/app/build/outputs/bundle/release/app-release.aab

# Or build APK (alternative)
# ./gradlew assembleRelease
# Output: android/app/build/outputs/apk/release/app-release.apk

cd ../
```

### Step 5: Upload to Google Play

1. In Google Play Console, go to "Release" → "Production"
2. Click "Create New Release"
3. Upload your `app-release.aab` or `app-release.apk`
4. Click "Review Release"
5. Complete store listing:

#### Store Listing Tab

- **Short Description** (80 chars):
  "Split expenses with friends easily"

- **Full Description** (4000 chars):
  ```
  ExpenseSettle makes it easy to track and split expenses with friends and family.

  Features:
  - Track expenses for groups
  - Split costs automatically
  - See who owes whom
  - Mark payments as settled
  - Real-time balance updates
  - Works offline

  Perfect for:
  - Roommates sharing rent and utilities
  - Friends splitting dinner bills
  - Travel groups sharing costs
  - Family events
  ```

- **App Category**: Finance
- **Content Rating Questionnaire**: Complete it
- **Screenshots** (Required):
  - Phone: 4-8 screenshots (1080x1920px)
  - Tablet: 4-8 screenshots (1600x2560px)
  - Focus on features, not just screens

- **Featured Graphic**: 1024x500px
- **Promotional Video**: YouTube URL (optional)

#### Content Rating Tab

- Complete questionnaire:
  - Advertising: Select appropriate
  - Data Collection: Select appropriate
  - Content: No mature content
  - Gets rating (usually 3+)

#### Pricing & Distribution Tab

- **Price**: Free
- **Countries**: Select all or choose specific ones
- **Devices**: Target phones and tablets
- **Android versions**: Min API 24, Target API 34+

#### Release Notes Tab

```
Version 1.0.0 - Initial Release
- Split expenses with friends
- Track group expenses
- Real-time balance updates
- Offline functionality
```

### Step 6: Submit for Review

1. Review all information
2. Click "Save"
3. Click "Review Release"
4. Review compliance
5. Click "Start Rollout to Production"
6. Confirm with OTP sent to email

**Review Time**: 2-4 hours (usually faster than iOS)

---

## Testing Before Submission

### Test on Real Devices

```bash
# For iOS
npx cap run ios
# Test all features on actual iPhone

# For Android
npx cap run android
# Test all features on actual Android phone
```

### Checklist Before Submission

- [ ] App loads without errors
- [ ] All navigation works
- [ ] Login/authentication works
- [ ] Can create expenses
- [ ] Can view groups and payments
- [ ] Can mark payments as paid
- [ ] Camera works (if testing)
- [ ] Notifications work (if Firebase setup)
- [ ] App name and branding correct
- [ ] Privacy policy accessible
- [ ] Support contact available
- [ ] No broken links
- [ ] App icon displays correctly
- [ ] Splash screen shows correctly
- [ ] Performance is smooth (no lag)

---

## Post-Launch Checklist

After app goes live:

- [ ] Monitor App Store reviews
- [ ] Monitor Google Play reviews
- [ ] Setup crash reporting (Firebase Crashlytics)
- [ ] Setup analytics (Firebase Analytics)
- [ ] Monitor performance metrics
- [ ] Plan first update with bug fixes
- [ ] Create marketing materials

---

## Updating Your App

### When You Make Changes

```bash
# 1. Make changes to web app
# 2. Build
npm run build

# 3. Sync with native
npx cap copy

# 4. For iOS:
# - Increment version/build in Xcode
# - Archive and upload

# 5. For Android:
# - Increment versionCode in build.gradle
# - Build release AAB
# - Upload to Play Store
```

### Version Numbers

- **Semantic Versioning**: MAJOR.MINOR.PATCH
- First release: 1.0.0
- Bug fixes: 1.0.1
- Features: 1.1.0
- Major changes: 2.0.0

---

## Troubleshooting

### iOS

**"No matching provisioning profile found"**
- Create provisioning profile in Apple Developer
- Download and open in Xcode

**"Code signing failed"**
- Check Bundle ID matches App ID
- Verify certificate in Keychain
- Check Team is set in Xcode

**"App rejected by review"**
- Read rejection reasons carefully
- Fix issues (usually privacy policy or crashes)
- Resubmit with explanation

### Android

**"Keystore not found"**
- Verify path in build.gradle is correct
- Keystore file must exist at that path

**"Gradle build failed"**
```bash
cd android
./gradlew clean
./gradlew bundleRelease
cd ../
```

**"App crashes on startup"**
- Check logcat for errors
- Ensure all permissions granted
- Verify Firebase credentials if using

---

## Support Resources

- **Apple**: https://developer.apple.com
- **Google Play**: https://play.google.com/console
- **Capacitor**: https://capacitorjs.com/docs/ios and /docs/android
- **Stack Overflow**: Tag your questions with `capacitor`, `ios`, `android`

---

## Typical Timeline

| Step | Duration |
|------|----------|
| Development & Testing | 1-2 weeks |
| Prepare materials (icons, screenshots) | 1-3 days |
| iOS review | 24-48 hours |
| Android review | 2-4 hours |
| **Total** | **2-3 weeks** |

---

**Your app is now on the world's app stores!** 🎉
