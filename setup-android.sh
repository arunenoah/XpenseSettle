#!/bin/bash

# ExpenseSettle Android Setup Script
# Run this after Android Studio is installed

set -e

echo "================================"
echo "ExpenseSettle Android Setup"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check Java
echo "Checking Java..."
if command -v java &> /dev/null; then
    java_version=$(java -version 2>&1 | head -1)
    echo -e "${GREEN}✓ Java installed: $java_version${NC}"
else
    echo -e "${RED}✗ Java not found${NC}"
    echo "  Install with: brew install openjdk@11"
    exit 1
fi

# Setup Android SDK paths
echo ""
echo "Setting up Android SDK paths..."

if [ -z "$ANDROID_SDK_ROOT" ]; then
    export ANDROID_SDK_ROOT=$HOME/Library/Android/sdk
    echo 'export ANDROID_SDK_ROOT=$HOME/Library/Android/sdk' >> ~/.zshrc
    echo 'export PATH=$ANDROID_SDK_ROOT/platform-tools:$PATH' >> ~/.zshrc
    echo 'export PATH=$ANDROID_SDK_ROOT/tools:$PATH' >> ~/.zshrc
    source ~/.zshrc
    echo -e "${GREEN}✓ Android SDK paths added to ~/.zshrc${NC}"
fi

# Check Android SDK
echo ""
echo "Checking Android SDK..."
if [ -d "$ANDROID_SDK_ROOT" ]; then
    echo -e "${GREEN}✓ Android SDK found at: $ANDROID_SDK_ROOT${NC}"
else
    echo -e "${RED}✗ Android SDK not found${NC}"
    echo "  Make sure Android Studio is installed at /Applications/Android Studio.app"
    echo "  Or set ANDROID_SDK_ROOT environment variable"
    exit 1
fi

# Check platform-tools
echo ""
echo "Checking Android tools..."
if [ -f "$ANDROID_SDK_ROOT/platform-tools/adb" ]; then
    echo -e "${GREEN}✓ adb (Android Debug Bridge) found${NC}"
else
    echo -e "${YELLOW}! Installing platform-tools...${NC}"
    mkdir -p "$ANDROID_SDK_ROOT/cmdline-tools"
fi

# Create emulator if not exists
echo ""
echo "Creating Android Virtual Device..."
if [ -d "$HOME/.android/avd/Pixel_5.avd" ]; then
    echo -e "${GREEN}✓ Emulator Pixel_5 already exists${NC}"
else
    echo -e "${YELLOW}! Creating new emulator (this may take a few minutes)...${NC}"

    # This requires SDK to be set up, so it might not work here
    # User may need to create via Android Studio GUI
    echo -e "${YELLOW}! Please create emulator manually:${NC}"
    echo "  1. Open Android Studio"
    echo "  2. Tools > Device Manager"
    echo "  3. Click 'Create Device'"
    echo "  4. Select 'Pixel 5'"
    echo "  5. Select 'Android 13' or higher"
    echo "  6. Click 'Finish'"
fi

echo ""
echo "================================"
echo "Android Setup Complete! ✓"
echo "================================"
echo ""
echo "Next Steps:"
echo ""
echo "1. OPEN ANDROID STUDIO:"
echo "   open -a \"Android Studio\""
echo ""
echo "2. FINISH INSTALLATION:"
echo "   - Click 'Next' through all setup dialogs"
echo "   - Accept licenses"
echo "   - Wait for SDK components to download"
echo ""
echo "3. CREATE EMULATOR (if not done):"
echo "   - Tools > Device Manager"
echo "   - Create Device > Pixel 5 > Android 13+"
echo ""
echo "4. START EMULATOR:"
echo "   \$ANDROID_SDK_ROOT/emulator/emulator -avd Pixel_5 &"
echo ""
echo "5. RUN CAPACITOR SETUP:"
echo "   ./setup-mobile.sh"
echo ""
echo "6. TEST ON ANDROID:"
echo "   npx cap run android"
echo ""
echo "================================"
echo ""
