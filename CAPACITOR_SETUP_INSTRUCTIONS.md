# Capacitor PDF Download - Quick Setup Guide

## What We've Done (Backend)

✅ Updated Laravel to use proper download headers
✅ Created JavaScript handler for Capacitor apps
✅ Automatically detects if running in Capacitor vs web browser

## What You Need to Do (Capacitor App)

### Step 1: Install Capacitor Browser Plugin

```bash
npm install @capacitor/browser
npx cap sync
```

### Step 2: Rebuild Your Assets

```bash
npm run build
# or
npm run dev
```

### Step 3: Sync with Capacitor

```bash
npx cap sync android
npx cap open android
```

### Step 4: Test

1. Build and run your Android app
2. Navigate to a group's dashboard or history page
3. Click the red PDF download FAB button
4. PDF should open in the system browser
5. Download from the browser

## How It Works

The JavaScript code (`capacitor-pdf-handler.js`) automatically:

1. **Detects** if running in Capacitor native app
2. **Intercepts** PDF download link clicks
3. **Opens** PDF in system browser using Capacitor Browser plugin
4. **Falls back** to normal download if plugin not available

## No Code Changes Needed!

The handler is already imported in `app.js` and will work automatically once you:
- Install the Browser plugin
- Rebuild your assets
- Sync with Capacitor

## Troubleshooting

### PDF still not downloading?

1. **Check plugin installation:**
   ```bash
   npm list @capacitor/browser
   ```

2. **Check console logs:**
   Open Chrome DevTools connected to your Android app and look for:
   - "Initializing PDF handlers..."
   - "Capacitor available: true"
   - "Browser plugin available: true"

3. **Verify build:**
   Make sure you ran `npm run build` after adding the plugin

4. **Clear cache:**
   ```bash
   npx cap sync android --force
   ```

### Alternative: Manual Testing

You can test if the Browser plugin works by running this in your app's console:

```javascript
Capacitor.Plugins.Browser.open({ url: 'https://www.google.com' });
```

If Google opens in a browser, the plugin is working!

## Optional: Download to Device

If you want to download PDFs directly to the device instead of opening in browser, see `CAPACITOR_PDF_DOWNLOAD.md` for the Filesystem API approach.

## Support

- Capacitor Browser Docs: https://capacitorjs.com/docs/apis/browser
- Capacitor Filesystem Docs: https://capacitorjs.com/docs/apis/filesystem
