# Capacitor PDF Download Configuration

## Issue
PDF downloads in Capacitor apps require special handling using Capacitor plugins.

## Solution Options

### Option 1: Use Capacitor Browser Plugin (Recommended - Easiest)

This opens the PDF in the system browser where it can be downloaded normally.

#### 1. Install the plugin:
```bash
npm install @capacitor/browser
npx cap sync
```

#### 2. Add to your frontend code:

```javascript
import { Browser } from '@capacitor/browser';

// When PDF download button is clicked
async function downloadPDF(url) {
    await Browser.open({ url: url });
}
```

#### 3. Update the FAB click handler:

In your Vue/React component or vanilla JS:

```javascript
// Find all PDF download links
document.querySelectorAll('a[href*="export-pdf"]').forEach(link => {
    link.addEventListener('click', async (e) => {
        e.preventDefault();
        const url = link.getAttribute('href');
        
        // Check if running on mobile
        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
            await Browser.open({ url: url });
        } else {
            // Web browser - use normal download
            window.open(url, '_blank');
        }
    });
});
```

---

### Option 2: Use Capacitor Filesystem Plugin (Advanced)

This downloads the file directly to the device.

#### 1. Install plugins:
```bash
npm install @capacitor/filesystem
npm install @capacitor/share
npx cap sync
```

#### 2. Add permissions to AndroidManifest.xml:
```xml
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
```

#### 3. Implement download function:

```javascript
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

async function downloadPDF(url, filename) {
    try {
        // Show loading indicator
        console.log('Downloading PDF...');
        
        // Fetch the PDF
        const response = await fetch(url);
        const blob = await response.blob();
        
        // Convert to base64
        const reader = new FileReader();
        reader.readAsDataURL(blob);
        
        reader.onloadend = async () => {
            const base64Data = reader.result.split(',')[1];
            
            // Save to device
            const savedFile = await Filesystem.writeFile({
                path: filename,
                data: base64Data,
                directory: Directory.Documents,
            });
            
            console.log('PDF saved:', savedFile.uri);
            
            // Optionally share the file
            await Share.share({
                title: 'Group Statement',
                text: 'Your expense group statement',
                url: savedFile.uri,
                dialogTitle: 'Share PDF',
            });
            
            alert('PDF downloaded successfully!');
        };
        
    } catch (error) {
        console.error('Error downloading PDF:', error);
        alert('Failed to download PDF. Please try again.');
    }
}

// Usage
document.querySelectorAll('a[href*="export-pdf"]').forEach(link => {
    link.addEventListener('click', async (e) => {
        e.preventDefault();
        
        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
            const url = link.getAttribute('href');
            const filename = `Group_Statement_${Date.now()}.pdf`;
            await downloadPDF(url, filename);
        } else {
            // Web - normal download
            window.location.href = link.getAttribute('href');
        }
    });
});
```

---

### Option 3: Simple Inline PDF Viewer

Open PDF in a new page within the app:

```javascript
// Just open in same window - Capacitor will handle it
document.querySelectorAll('a[href*="export-pdf"]').forEach(link => {
    link.addEventListener('click', (e) => {
        if (window.Capacitor && window.Capacitor.isNativePlatform()) {
            // Let Capacitor handle it
            // Remove preventDefault to allow normal navigation
        }
    });
});
```

---

## Recommended Approach for Your App

**Use Option 1 (Browser Plugin)** - It's the simplest and most reliable:

### Step 1: Install Browser plugin
```bash
npm install @capacitor/browser
npx cap sync
```

### Step 2: Add this to your main JavaScript file:

```javascript
import { Browser } from '@capacitor/browser';
import { Capacitor } from '@capacitor/core';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    setupPDFDownloads();
});

function setupPDFDownloads() {
    // Handle all PDF export links
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('a[href*="export-pdf"]');
        
        if (link && Capacitor.isNativePlatform()) {
            e.preventDefault();
            const url = link.href;
            
            try {
                await Browser.open({ 
                    url: url,
                    presentationStyle: 'popover' // or 'fullscreen'
                });
            } catch (error) {
                console.error('Error opening PDF:', error);
                // Fallback to normal navigation
                window.location.href = url;
            }
        }
    });
}
```

### Step 3: Update capacitor.config.ts (if needed):

```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.yourapp.expensesettle',
  appName: 'ExpenseSettle',
  webDir: 'dist',
  server: {
    androidScheme: 'https'
  },
  plugins: {
    Browser: {
      presentationStyle: 'popover'
    }
  }
};

export default config;
```

---

## Testing

1. Build and run your Capacitor app
2. Click the red PDF FAB button
3. PDF should open in system browser
4. User can download from browser's download button

---

## Why This Works

- ✅ Capacitor Browser plugin opens URLs in system browser
- ✅ System browser handles PDF downloads natively
- ✅ No special permissions needed
- ✅ Works on both Android and iOS
- ✅ Simple implementation
- ✅ Reliable across all devices

---

## Alternative: Keep in App

If you want to keep the PDF in-app, use a PDF viewer plugin:

```bash
npm install @awesome-cordova-plugins/document-viewer
```

But the Browser plugin approach is simpler and more reliable!
