# Android WebView Configuration for PDF Downloads

## Issue
PDF downloads may not work in Android WebView without proper configuration.

## Required WebView Settings

Add the following configuration to your Android app's WebView setup:

```java
// Enable JavaScript
webView.getSettings().setJavaScriptEnabled(true);

// Enable DOM storage
webView.getSettings().setDomStorageEnabled(true);

// Allow file access
webView.getSettings().setAllowFileAccess(true);

// Enable downloads
webView.setDownloadListener(new DownloadListener() {
    @Override
    public void onDownloadStart(String url, String userAgent,
            String contentDisposition, String mimetype, long contentLength) {
        
        // Create download request
        DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
        request.setMimeType(mimetype);
        
        // Get filename from Content-Disposition header
        String filename = URLUtil.guessFileName(url, contentDisposition, mimetype);
        request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, filename);
        
        // Show notification
        request.setNotificationVisibility(
            DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
        );
        request.setTitle(filename);
        
        // Start download
        DownloadManager dm = (DownloadManager) getSystemService(DOWNLOAD_SERVICE);
        dm.enqueue(request);
        
        // Show toast
        Toast.makeText(getApplicationContext(), "Downloading PDF...", Toast.LENGTH_LONG).show();
    }
});
```

## Required Permissions

Add these permissions to your `AndroidManifest.xml`:

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
```

## For Android 10+ (API 29+)

Add to your `AndroidManifest.xml` application tag:

```xml
<application
    android:requestLegacyExternalStorage="true"
    ...>
```

Or use scoped storage:

```java
request.setDestinationInExternalFilesDir(
    context, 
    Environment.DIRECTORY_DOWNLOADS, 
    filename
);
```

## Testing

1. Click the red PDF download FAB
2. Check if download notification appears
3. Check Downloads folder for the PDF file
4. File should be named: `Group_History_[GroupName]_[Date].pdf`

## Troubleshooting

If downloads still don't work:

1. **Check permissions**: Ensure storage permissions are granted
2. **Check WebView version**: Update to latest WebView
3. **Check network**: Ensure app has internet access
4. **Check logs**: Look for download errors in Logcat
5. **Test in browser**: Try opening the URL in Chrome to verify backend works

## Alternative: Open in External Browser

If WebView downloads are problematic, you can open PDFs in external browser:

```java
webView.setWebViewClient(new WebViewClient() {
    @Override
    public boolean shouldOverrideUrlLoading(WebView view, String url) {
        if (url.contains("/export-pdf")) {
            Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
            startActivity(intent);
            return true;
        }
        return false;
    }
});
```

## Backend Changes Made

The Laravel backend now:
- Uses `streamDownload()` with explicit headers
- Sets proper `Content-Type: application/pdf`
- Sets `Content-Disposition: attachment`
- Includes cache control headers
- Uses `download` attribute on links
