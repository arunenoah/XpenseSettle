# 🚀 Quick Start Guide - ExpenseSettle Application

## Prerequisites
- PHP 8.2 or higher
- Composer installed
- MySQL database running
- Node.js and npm (for frontend assets)

---

## Step-by-Step Setup

### 1. Database Configuration

Create a MySQL database:
```sql
CREATE DATABASE expensesettle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expensesettle
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Run Migrations

```bash
# Run all migrations to create database tables
php artisan migrate
```

This will create the following tables:
- users
- groups
- group_members
- expenses
- expense_splits
- payments
- comments
- attachments
- sessions

### 5. Seed Sample Data

```bash
# Populate database with sample users and expenses
php artisan db:seed
```

This creates:
- **5 Users** with realistic data
- **3 Groups** (Roommates, Trip, Office Lunch)
- **6 Expenses** with different split types
- **5 Comments** on various expenses
- **Multiple Payments** in different statuses

### 6. Create Storage Link

```bash
# Create symbolic link for file storage
php artisan storage:link
```

### 7. Build Frontend Assets

```bash
# Build assets for development
npm run dev

# OR for production
npm run build
```

### 8. Start the Application

```bash
# Start Laravel development server
php artisan serve
```

The application will be available at: **http://localhost:8000**

---

## 🔐 Test User Credentials

All users have the password: **`password`**

| Email | Name | Role | Groups |
|-------|------|------|--------|
| john@example.com | John Doe | Admin | Apartment 4B - Roommates, Beach Weekend Trip |
| jane@example.com | Jane Smith | Member | Apartment 4B - Roommates, Beach Weekend Trip |
| mike@example.com | Mike Johnson | Admin | Apartment 4B - Roommates, Office Lunch Group |
| sarah@example.com | Sarah Williams | Admin | Beach Weekend Trip, Office Lunch Group |
| alex@example.com | Alex Brown | Member | Beach Weekend Trip, Office Lunch Group |

---

## 📊 Sample Data Overview

### Group 1: Apartment 4B - Roommates
**Members**: John (admin), Jane, Mike

**Expenses**:
1. **Monthly Rent - December** ($1,800)
   - Split: Equal (3 ways)
   - Status: Pending (Jane paid her share)
   
2. **Weekly Groceries** ($245.50)
   - Split: Custom amounts
   - Status: Pending
   
3. **Electricity & Water Bill** ($180)
   - Split: Percentage (40%, 30%, 30%)
   - Status: Fully Paid ✅

### Group 2: Beach Weekend Trip
**Members**: Sarah (admin), John, Jane, Alex

**Expenses**:
1. **Beach Resort Hotel** ($600)
   - Split: Equal (4 ways)
   - Status: Pending (John & Jane paid)
   
2. **Seafood Dinner** ($180)
   - Split: Equal (4 ways)
   - Status: Fully Paid ✅

### Group 3: Office Lunch Group
**Members**: Mike (admin), Alex, Sarah

**Expenses**:
1. **Friday Team Lunch** ($95)
   - Split: Equal (3 ways)
   - Status: Pending

---

## 🧪 Testing Scenarios

### Scenario 1: View Dashboard
1. Login as `john@example.com`
2. Navigate to Dashboard
3. See summary of all expenses across groups
4. View pending payments

### Scenario 2: Mark Payment as Paid
1. Login as `mike@example.com`
2. Go to "Apartment 4B - Roommates" group
3. Find "Monthly Rent - December" expense
4. Click "Mark as Paid" for your share
5. Optionally upload proof of payment

### Scenario 3: Create New Expense
1. Login as `sarah@example.com`
2. Go to "Beach Weekend Trip" group
3. Click "Add Expense"
4. Create expense with percentage split:
   - Title: "Car Rental"
   - Amount: $200
   - Split Type: Percentage
   - Sarah: 25%, John: 25%, Jane: 25%, Alex: 25%

### Scenario 4: Add Comment
1. Login as any user
2. View any expense
3. Scroll to comments section
4. Add a comment with optional attachment

### Scenario 5: Export Data
1. Login as group admin
2. Go to group page
3. Click "Export" dropdown
4. Choose:
   - Export Expenses (CSV)
   - Export Balances (CSV)
   - Export Summary Report (CSV)

### Scenario 6: View Payment History
1. Login as any user
2. Navigate to "Payments" section
3. Filter by:
   - Status (All, Pending, Paid, Rejected)
   - Group
4. View detailed payment information

---

## 🎮 Features to Test

### ✅ Core Features
- [x] User authentication (login/logout)
- [x] Create and manage groups
- [x] Add members to groups
- [x] Create expenses with different split types
- [x] Mark payments as paid
- [x] View group balances
- [x] Add comments to expenses
- [x] Upload attachments

### ✅ Advanced Features
- [x] Percentage-based splits
- [x] Custom amount splits
- [x] Payment approval workflow
- [x] Payment reminders
- [x] CSV export functionality
- [x] Group activity timeline
- [x] User statistics and achievements
- [x] Trust score calculation

---

## 🔧 Useful Commands

```bash
# Reset database and reseed
php artisan migrate:fresh --seed

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests (when created)
php artisan test

# Check routes
php artisan route:list

# Open tinker (Laravel REPL)
php artisan tinker
```

---

## 🐛 Troubleshooting

### Issue: "Class not found" errors
**Solution**: Run `composer dump-autoload`

### Issue: Storage permission errors
**Solution**: 
```bash
chmod -R 775 storage bootstrap/cache
```

### Issue: Database connection failed
**Solution**: 
- Check MySQL is running
- Verify `.env` database credentials
- Test connection: `php artisan migrate:status`

### Issue: Assets not loading
**Solution**: 
```bash
npm run build
php artisan storage:link
```

---

## 📱 API Testing (Optional)

If you want to test the API endpoints:

```bash
# Get user token (implement auth first)
POST /api/login
{
    "email": "john@example.com",
    "password": "password"
}

# Then use token in headers
Authorization: Bearer {token}
```

---

## 🎯 Next Steps

After testing the application:

1. **Create Views** - Build the frontend Blade templates
2. **Add Routes** - Configure all routes in `web.php`
3. **Implement Email Notifications** - Set up mail configuration
4. **Add Real-time Updates** - Integrate WebSockets/Pusher
5. **Create Tests** - Write unit and feature tests
6. **Deploy** - Set up production environment

---

## 📞 Support

For issues or questions:
- Check `PROJECT_STATUS.md` for implementation status
- Review `IMPLEMENTATION_GUIDE.md` for detailed guides
- Check `ENHANCEMENT_SUMMARY.md` for new features

---

## 🎉 Happy Testing!

The application is now ready to use with realistic sample data. Explore all features and test different scenarios!
