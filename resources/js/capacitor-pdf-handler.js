/**
 * Capacitor PDF Download Handler
 * 
 * This module handles PDF downloads in Capacitor apps.
 * For web browsers, it uses normal download behavior.
 * For Capacitor native apps, it uses the Browser plugin to open PDFs.
 */

// Check if Capacitor Browser plugin is available
let Browser = null;
let Capacitor = null;

// Try to import Capacitor plugins if available
if (window.Capacitor) {
    Capacitor = window.Capacitor;
    
    // Browser plugin should be available if installed
    if (window.Capacitor.Plugins && window.Capacitor.Plugins.Browser) {
        Browser = window.Capacitor.Plugins.Browser;
    }
}

/**
 * Initialize PDF download handlers
 */
function initializePDFHandlers() {
    console.log('Initializing PDF handlers...');
    console.log('Capacitor available:', !!Capacitor);
    console.log('Is native platform:', Capacitor?.isNativePlatform());
    console.log('Browser plugin available:', !!Browser);
    
    // Handle clicks on PDF export links
    document.addEventListener('click', handlePDFClick, true);
}

/**
 * Handle PDF download link clicks
 */
async function handlePDFClick(event) {
    // Find if clicked element or its parent is a PDF export link
    const link = event.target.closest('a[href*="export-pdf"]');
    
    if (!link) return;
    
    // Check if we're in a Capacitor native app
    if (Capacitor && Capacitor.isNativePlatform()) {
        event.preventDefault();
        event.stopPropagation();
        
        const url = link.href;
        console.log('PDF download requested:', url);
        
        try {
            if (Browser) {
                // Use Capacitor Browser plugin to open PDF
                console.log('Opening PDF with Capacitor Browser...');
                await Browser.open({ 
                    url: url,
                    presentationStyle: 'popover'
                });
            } else {
                // Fallback: Try to open in new window
                console.log('Browser plugin not available, using fallback...');
                window.open(url, '_system');
            }
        } catch (error) {
            console.error('Error opening PDF:', error);
            
            // Last resort: navigate to URL
            alert('Opening PDF in browser...');
            window.location.href = url;
        }
    } else {
        // Web browser - let normal download behavior work
        console.log('Web browser detected, using normal download');
    }
}

/**
 * Alternative: Download PDF using Filesystem API
 * (Requires @capacitor/filesystem plugin)
 */
async function downloadPDFToDevice(url, filename) {
    if (!window.Capacitor?.Plugins?.Filesystem) {
        console.error('Filesystem plugin not available');
        return false;
    }
    
    const { Filesystem } = window.Capacitor.Plugins;
    const { Directory } = window.Capacitor.Plugins.Filesystem;
    
    try {
        console.log('Downloading PDF to device...');
        
        // Fetch the PDF
        const response = await fetch(url);
        const blob = await response.blob();
        
        // Convert to base64
        const base64Data = await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result.split(',')[1]);
            reader.readAsDataURL(blob);
        });
        
        // Save to device
        const savedFile = await Filesystem.writeFile({
            path: filename,
            data: base64Data,
            directory: Directory.Documents,
        });
        
        console.log('PDF saved:', savedFile.uri);
        
        // Show success message
        alert('PDF downloaded successfully!');
        
        // Optionally share the file
        if (window.Capacitor?.Plugins?.Share) {
            const { Share } = window.Capacitor.Plugins;
            await Share.share({
                title: 'Group Statement',
                url: savedFile.uri,
            });
        }
        
        return true;
    } catch (error) {
        console.error('Error downloading PDF:', error);
        alert('Failed to download PDF. Please try again.');
        return false;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePDFHandlers);
} else {
    initializePDFHandlers();
}

// Export for use in other modules
export { initializePDFHandlers, downloadPDFToDevice };
