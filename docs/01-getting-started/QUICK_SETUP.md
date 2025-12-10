# ⚡ Quick Setup - ExpenseSettle

## Option 1: Automated Setup (Recommended)

Run the setup script:
```bash
./setup.sh
```

This will:
- Create .env file
- Generate app key
- Create database
- Run migrations
- Seed sample data
- Install dependencies
- Build assets

---

## Option 2: Manual Setup

### Step 1: Create Database

```sql
CREATE DATABASE expensesettle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 2: Configure Environment

Copy `.env.example` to `.env` and update:
```env
DB_DATABASE=expensesettle
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 3: Run Setup Commands

```bash
# Generate app key
php artisan key:generate

# Install dependencies
composer install

# Run migrations
php artisan migrate

# Seed sample data
php artisan db:seed

# Create storage link
php artisan storage:link

# Install npm dependencies (optional)
npm install && npm run build
```

### Step 4: Start Application

```bash
php artisan serve
```

Visit: **http://localhost:8000**

---

## 🔐 Test Accounts

**All passwords**: `password`

| Email | Role | Groups |
|-------|------|--------|
| john@example.com | Admin | Roommates, Trip |
| jane@example.com | Member | Roommates, Trip |
| mike@example.com | Admin | Roommates, Lunch |
| sarah@example.com | Admin | Trip, Lunch |
| alex@example.com | Member | Trip, Lunch |

---

## 📊 Sample Data Includes

- **5 Users** with different roles
- **3 Groups** (Roommates, Beach Trip, Office Lunch)
- **6 Expenses** with various split types:
  - Equal splits
  - Custom amount splits
  - Percentage splits
- **Multiple Payments** (some paid, some pending)
- **5 Comments** on expenses

---

## 🧪 Quick Test Scenarios

1. **Login** as john@example.com
2. **View Dashboard** - See all your groups and expenses
3. **Go to "Apartment 4B - Roommates"** group
4. **View "Monthly Rent"** expense - See Jane already paid
5. **Mark your payment** as paid
6. **Add a comment** to the expense
7. **Create new expense** with percentage split
8. **Export group data** to CSV

---

## 🔧 Troubleshooting

**Database error?**
```bash
# Check database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'expensesettle';"

# Recreate if needed
mysql -u root -p -e "DROP DATABASE IF EXISTS expensesettle; CREATE DATABASE expensesettle;"
```

**Permission errors?**
```bash
chmod -R 775 storage bootstrap/cache
```

**Clear cache:**
```bash
php artisan optimize:clear
```

---

## 🚀 Ready to Go!

Once setup is complete, you can:
- ✅ Login with any test account
- ✅ View and manage expenses
- ✅ Mark payments as paid
- ✅ Add comments and attachments
- ✅ Export data to CSV
- ✅ View group timelines
- ✅ Check user statistics

See `START_APPLICATION.md` for detailed testing scenarios!
