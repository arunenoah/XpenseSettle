#!/bin/bash

# ExpenseSettle Mobile App Setup Script
# This script automates the Capacitor setup process

set -e  # Exit on error

echo "================================"
echo "ExpenseSettle Mobile App Setup"
echo "================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo -e "${RED}✗ Node.js is not installed${NC}"
    echo "  Install from: https://nodejs.org"
    exit 1
fi
echo -e "${GREEN}✓ Node.js detected${NC}"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo -e "${RED}✗ npm is not installed${NC}"
    exit 1
fi
echo -e "${GREEN}✓ npm detected${NC}"

echo ""
echo "================================"
echo "Step 1: Installing Dependencies"
echo "================================"

# Install Capacitor packages
echo "Installing Capacitor CLI and plugins..."
npm install --save @capacitor/core @capacitor/cli \
    @capacitor/app @capacitor/haptics @capacitor/keyboard \
    @capacitor/splash-screen @capacitor/status-bar \
    @capacitor/ios @capacitor/android \
    @capacitor/camera @capacitor/device @capacitor/geolocation \
    @capacitor/local-notifications @capacitor/push-notifications \
    @capacitor/storage

echo -e "${GREEN}✓ Dependencies installed${NC}"

echo ""
echo "================================"
echo "Step 2: Building Web App"
echo "================================"

echo "Building Vite project..."
npm run build

if [ -d "public" ]; then
    echo -e "${GREEN}✓ Web app built successfully${NC}"
else
    echo -e "${RED}✗ Build failed - public directory not found${NC}"
    exit 1
fi

echo ""
echo "================================"
echo "Step 3: Initializing Capacitor"
echo "================================"

# Initialize Capacitor (will use capacitor.config.ts we created)
if npx cap init --skip-prompt; then
    echo -e "${GREEN}✓ Capacitor initialized${NC}"
else
    echo -e "${YELLOW}! Capacitor already initialized${NC}"
fi

echo ""
echo "================================"
echo "Step 4: Adding Platforms"
echo "================================"

# Add iOS
echo "Adding iOS platform..."
if npx cap add ios; then
    echo -e "${GREEN}✓ iOS platform added${NC}"
else
    echo -e "${YELLOW}! iOS platform already exists${NC}"
fi

echo ""

# Add Android
echo "Adding Android platform..."
if npx cap add android; then
    echo -e "${GREEN}✓ Android platform added${NC}"
else
    echo -e "${YELLOW}! Android platform already exists${NC}"
fi

echo ""
echo "================================"
echo "Step 5: Syncing with Platforms"
echo "================================"

npx cap sync

echo -e "${GREEN}✓ Platforms synchronized${NC}"

echo ""
echo "================================"
echo "Setup Complete! ✓"
echo "================================"
echo ""
echo "Next Steps:"
echo ""
echo "1. CONFIGURE iOS:"
echo "   - Open: open ios/App/App.xcworkspace"
echo "   - Set Bundle ID: com.expensesettle.app"
echo "   - Set Team: Your Apple Developer Team"
echo "   - Add Capabilities: Camera, Push Notifications"
echo ""
echo "2. CONFIGURE Android:"
echo "   - Open: open -a \"Android Studio\" android/"
echo "   - Sync Gradle"
echo "   - Set Min SDK: API 24+"
echo "   - Set Target SDK: API 34+"
echo ""
echo "3. CONFIGURE BACKEND:"
echo "   - Read: LARAVEL_MOBILE_SETUP.md"
echo "   - Configure CORS"
echo "   - Setup session driver"
echo ""
echo "4. TEST LOCALLY:"
echo "   - iOS:    npx cap run ios"
echo "   - Android: npx cap run android"
echo ""
echo "5. SETUP PUSH NOTIFICATIONS:"
echo "   - Read: FIREBASE_SETUP.md"
echo "   - Create Firebase project"
echo "   - Add Android and iOS apps"
echo ""
echo "================================"
echo "Documentation:"
echo "================================"
echo "- Setup Guide:   CAPACITOR_SETUP_GUIDE.md"
echo "- Laravel Setup: LARAVEL_MOBILE_SETUP.md"
echo "- Firebase:      FIREBASE_SETUP.md"
echo ""
