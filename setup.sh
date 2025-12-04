#!/bin/bash

echo "🚀 ExpenseSettle Application Setup"
echo "=================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  .env file not found. Copying from .env.example...${NC}"
    cp .env.example .env
    echo -e "${GREEN}✅ .env file created${NC}"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo -e "${YELLOW}🔑 Generating application key...${NC}"
    php artisan key:generate
    echo -e "${GREEN}✅ Application key generated${NC}"
fi

# Prompt for database creation
echo ""
echo -e "${YELLOW}📊 Database Setup${NC}"
echo "Please ensure MySQL is running and you have the credentials ready."
echo ""
read -p "Database name (default: expensesettle): " DB_NAME
DB_NAME=${DB_NAME:-expensesettle}

read -p "Database username (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Database password: " DB_PASS
echo ""

# Update .env file
echo -e "${YELLOW}📝 Updating .env file...${NC}"
sed -i.bak "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
sed -i.bak "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i.bak "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
rm .env.bak
echo -e "${GREEN}✅ .env file updated${NC}"

# Create database
echo ""
echo -e "${YELLOW}🗄️  Creating database...${NC}"
mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database created successfully${NC}"
else
    echo -e "${RED}❌ Failed to create database. Please create it manually:${NC}"
    echo "   CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo ""
    read -p "Press Enter after creating the database manually..."
fi

# Install dependencies
echo ""
echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
else
    echo -e "${RED}❌ Failed to install Composer dependencies${NC}"
    exit 1
fi

# Run migrations
echo ""
echo -e "${YELLOW}🔄 Running database migrations...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migrations completed${NC}"
else
    echo -e "${RED}❌ Migrations failed${NC}"
    exit 1
fi

# Seed database
echo ""
echo -e "${YELLOW}🌱 Seeding database with sample data...${NC}"
php artisan db:seed --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Database seeded successfully${NC}"
else
    echo -e "${RED}❌ Seeding failed${NC}"
    exit 1
fi

# Create storage link
echo ""
echo -e "${YELLOW}🔗 Creating storage link...${NC}"
php artisan storage:link

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Storage link created${NC}"
else
    echo -e "${YELLOW}⚠️  Storage link may already exist${NC}"
fi

# Install npm dependencies
echo ""
echo -e "${YELLOW}📦 Installing npm dependencies...${NC}"
if command -v npm &> /dev/null; then
    npm install
    echo -e "${GREEN}✅ npm dependencies installed${NC}"
    
    echo ""
    echo -e "${YELLOW}🎨 Building frontend assets...${NC}"
    npm run build
    echo -e "${GREEN}✅ Assets built${NC}"
else
    echo -e "${YELLOW}⚠️  npm not found. Skipping frontend build.${NC}"
fi

# Summary
echo ""
echo "=================================="
echo -e "${GREEN}🎉 Setup Complete!${NC}"
echo "=================================="
echo ""
echo "📊 Sample Data Created:"
echo "  - 5 Users"
echo "  - 3 Groups"
echo "  - 6 Expenses"
echo "  - 5 Comments"
echo ""
echo "🔐 Login Credentials (password: password):"
echo "  - john@example.com"
echo "  - jane@example.com"
echo "  - mike@example.com"
echo "  - sarah@example.com"
echo "  - alex@example.com"
echo ""
echo "🚀 To start the application:"
echo "   php artisan serve"
echo ""
echo "Then visit: http://localhost:8000"
echo ""
