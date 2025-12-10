# 🗄️ Database Setup & Updates in Laravel Forge

## Complete Guide for Creating and Updating Database

---

## 🎯 Part 1: Creating Database in Forge

### Method 1: Create Database When Creating Site (Easiest)

**Step 1:** When creating a new site in Forge:

```
1. Go to your Server in Forge
2. Click "New Site"
3. Fill in:
   - Root Domain: yourdomain.com
   - Project Type: General PHP / Laravel
   - Web Directory: /public
   - PHP Version: 8.2
   
4. ✅ Check "Create Database"
5. Database Name: expensesettle_prod
6. Click "Add Site"
```

**What Happens:**
- ✅ Forge creates MySQL database
- ✅ Creates database user (usually `forge`)
- ✅ Generates secure password
- ✅ Updates `.env` file automatically

---

### Method 2: Create Database After Site is Created

**Step 1:** Go to Database Section

```
1. In Forge, click on your Server name
2. Click "Database" in left sidebar
3. You'll see existing databases listed
```

**Step 2:** Create New Database

```
1. Click "New Database" button
2. Fill in:
   - Name: expensesettle_prod
   - User: forge (or create new user)
3. Click "Add Database"
4. Forge will show you the password - COPY IT!
```

**Step 3:** Update Your Site's .env File

```
1. Go to your Site (not Server)
2. Click "Environment" tab
3. Find the database section:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expensesettle_prod
DB_USERNAME=forge
DB_PASSWORD=paste_the_password_here

4. Click "Save"
```

---

## 🔄 Part 2: Running Migrations (Creating Tables)

### Method 1: Using Forge Commands Tab (Recommended)

**Step 1:** Go to Commands

```
1. Click on your Site in Forge
2. Click "Commands" tab
3. You'll see a command input box
```

**Step 2:** Run Migration

```bash
php artisan migrate --force
```

**Step 3:** Click "Run Command"

**What You'll See:**
```
Migrating: 2025_12_04_030202_create_groups_table
Migrated:  2025_12_04_030202_create_groups_table (45.23ms)
Migrating: 2025_12_04_030203_create_group_members_table
Migrated:  2025_12_04_030203_create_group_members_table (38.12ms)
...
✅ All migrations completed!
```

---

### Method 2: Via SSH

**Step 1:** Connect to Server

```
1. In Forge, click your Site
2. Click "SSH" button (top right)
3. Terminal will open
```

**Step 2:** Navigate to Your Site

```bash
cd /home/forge/yourdomain.com
```

**Step 3:** Run Migrations

```bash
php artisan migrate --force
```

---

### Method 3: Automatic on Deploy (Best for Updates)

Your deployment script already includes migrations!

In Forge → Site → Deployment tab, you have:

```bash
# Run migrations (be careful in production!)
php artisan migrate --force
```

This means **every time you deploy**, migrations run automatically! ✅

---

## 📊 Part 3: Checking Database Status

### Check if Tables Were Created

**Option 1: Using Forge Commands**

```bash
php artisan migrate:status
```

**Output:**
```
Migration name ........................... Batch / Status
2025_12_04_030202_create_groups_table .... [1] Ran
2025_12_04_030203_create_group_members ... [1] Ran
2025_12_04_030204_create_expenses_table .. [1] Ran
...
```

**Option 2: Using MySQL**

```bash
# In SSH or Commands tab
mysql -u forge -p expensesettle_prod -e "SHOW TABLES;"
```

**Output:**
```
+---------------------------+
| Tables_in_expensesettle   |
+---------------------------+
| attachments               |
| cache                     |
| comments                  |
| expense_splits            |
| expenses                  |
| group_members             |
| groups                    |
| migrations                |
| payments                  |
| users                     |
+---------------------------+
```

---

## 🔄 Part 4: Updating Database (Adding New Tables/Columns)

### Scenario: You Added New Migration Files

**Example:** You created a new migration locally:

```bash
# On your local machine
php artisan make:migration add_profile_picture_to_users_table
```

### Step 1: Commit and Push to GitHub

```bash
git add database/migrations/
git commit -m "Add profile picture column to users"
git push origin main
```

### Step 2: Deploy in Forge

**Option A: Auto Deploy (if enabled)**
- Forge detects the push
- Runs deployment script
- Migrations run automatically ✅

**Option B: Manual Deploy**
```
1. Go to Forge → Your Site
2. Click "Deployment" tab
3. Click "Deploy Now"
4. Wait for completion
```

### Step 3: Verify Update

```bash
# In Forge Commands tab
php artisan migrate:status
```

You should see your new migration with `[Ran]` status!

---

## 🆕 Part 5: Creating Your First Admin User

After migrations run, create an admin user:

### Method 1: Using Tinker (Recommended)

**Step 1:** Open Tinker

```bash
# In Forge Commands tab or SSH
php artisan tinker
```

**Step 2:** Create User

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourdomain.com',
    'password' => Hash::make('SecurePass@123!'),
]);
```

**Step 3:** Exit Tinker

```php
exit
```

### Method 2: Using SQL Directly

```bash
mysql -u forge -p expensesettle_prod
```

```sql
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES (
    'Admin User',
    'admin@yourdomain.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    NOW()
);
```

**Note:** That password hash is for `password` - use for testing only!

---

## 🔍 Part 6: Viewing Database Data

### Option 1: Using phpMyAdmin (GUI)

**Step 1:** Enable phpMyAdmin in Forge

```
1. Server → Database tab
2. Click "Open phpMyAdmin" or "Open Adminer"
3. Login with your database credentials
```

**Step 2:** Browse Your Database

```
1. Select "expensesettle_prod" from left sidebar
2. Click on any table (e.g., "users")
3. View, edit, or delete data
```

### Option 2: Using MySQL CLI

```bash
# Connect to database
mysql -u forge -p expensesettle_prod

# Show all tables
SHOW TABLES;

# View users
SELECT * FROM users;

# View groups
SELECT * FROM groups;

# Count expenses
SELECT COUNT(*) FROM expenses;

# Exit
exit
```

### Option 3: Using TablePlus/Sequel Pro

**Connection Details:**
```
Host: your-server-ip (from Forge)
Port: 3306
Username: forge
Password: (from Forge → Database section)
Database: expensesettle_prod
```

**Important:** Add your IP to Forge firewall first!

```
1. Forge → Server → Network tab
2. Add your IP address
3. Port: 3306
4. Click "Add Rule"
```

---

## 🔄 Part 7: Common Database Update Scenarios

### Scenario 1: Add New Column to Existing Table

**Step 1:** Create Migration Locally

```bash
php artisan make:migration add_phone_to_users_table
```

**Step 2:** Edit Migration File

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('email');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('phone');
    });
}
```

**Step 3:** Push to GitHub

```bash
git add .
git commit -m "Add phone column to users"
git push origin main
```

**Step 4:** Deploy in Forge
- Auto-deploys if enabled
- Or click "Deploy Now"
- Migration runs automatically!

---

### Scenario 2: Add New Table

**Step 1:** Create Migration

```bash
php artisan make:migration create_notifications_table
```

**Step 2:** Define Schema

```php
public function up()
{
    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('message');
        $table->boolean('read')->default(false);
        $table->timestamps();
    });
}
```

**Step 3:** Push and Deploy

```bash
git add .
git commit -m "Add notifications table"
git push origin main
```

Forge deploys and runs migration automatically! ✅

---

### Scenario 3: Modify Existing Column

**Step 1:** Install doctrine/dbal (if not already)

```bash
# Locally
composer require doctrine/dbal
```

**Step 2:** Create Migration

```bash
php artisan make:migration modify_amount_column_in_expenses_table
```

**Step 3:** Edit Migration

```php
public function up()
{
    Schema::table('expenses', function (Blueprint $table) {
        $table->decimal('amount', 10, 2)->change();
    });
}
```

**Step 4:** Push and Deploy

```bash
git add .
git commit -m "Modify amount column precision"
git push origin main
```

---

## ⚠️ Part 8: Important Database Commands

### Check Migration Status

```bash
php artisan migrate:status
```

### Rollback Last Migration

```bash
php artisan migrate:rollback
```

### Rollback Specific Steps

```bash
php artisan migrate:rollback --step=2
```

### Fresh Migration (⚠️ DANGER - Deletes ALL data!)

```bash
# NEVER run this in production!
# php artisan migrate:fresh
```

### Seed Database (⚠️ Only in development!)

```bash
# Our seeder is protected - won't run in production
php artisan db:seed
```

---

## 🔒 Part 9: Database Backup & Restore

### Enable Automatic Backups

**Step 1:** Go to Backups

```
1. Forge → Server → Backups tab
2. Click "Enable Backups"
```

**Step 2:** Configure Backup

```
Frequency: Daily (recommended)
Time: 2:00 AM (low traffic time)
Storage: 
  - Amazon S3
  - DigitalOcean Spaces
  - Custom S3-compatible
Retention: 7 days (or more)
```

**Step 3:** Save Configuration

Forge will now backup your database daily!

### Manual Backup

**Via Forge:**
```
1. Server → Backups tab
2. Click "Backup Now"
3. Wait for completion
4. Download backup file
```

**Via SSH:**
```bash
# Create backup
mysqldump -u forge -p expensesettle_prod > backup_$(date +%Y%m%d).sql

# Download to local machine
# Use SFTP or Forge's file manager
```

### Restore from Backup

```bash
# Upload backup file to server first
# Then restore:
mysql -u forge -p expensesettle_prod < backup_20251204.sql
```

---

## 🛠️ Part 10: Troubleshooting

### Issue: "SQLSTATE[HY000] [1045] Access denied"

**Solution:**
```bash
# Check credentials in .env
# Get correct password from Forge → Database section
# Update .env and clear cache:
php artisan config:clear
```

### Issue: "Database does not exist"

**Solution:**
```bash
# Create database
mysql -u forge -p
CREATE DATABASE expensesettle_prod;
exit

# Or create in Forge UI
```

### Issue: "Migration table not found"

**Solution:**
```bash
# Run migrations
php artisan migrate --force
```

### Issue: "Column already exists"

**Solution:**
```bash
# Rollback last migration
php artisan migrate:rollback

# Fix migration file
# Run again
php artisan migrate
```

---

## ✅ Database Setup Checklist

After setting up database:

- [ ] Database created in Forge
- [ ] Credentials added to `.env`
- [ ] Config cache cleared
- [ ] Migrations run successfully
- [ ] All tables created
- [ ] Admin user created
- [ ] Can login to application
- [ ] Backups enabled
- [ ] Tested database connection
- [ ] Deployment script includes migrations

---

## 📝 Quick Reference Commands

```bash
# Check migration status
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback

# Clear config cache
php artisan config:clear

# Create user in tinker
php artisan tinker
User::create([...]);

# Check database connection
php artisan db:show

# View tables
mysql -u forge -p expensesettle_prod -e "SHOW TABLES;"
```

---

## 🎯 Summary

### Creating Database:
1. ✅ Create in Forge UI (Server → Database)
2. ✅ Update `.env` with credentials
3. ✅ Run `php artisan migrate --force`
4. ✅ Create admin user
5. ✅ Enable backups

### Updating Database:
1. ✅ Create migration locally
2. ✅ Push to GitHub
3. ✅ Deploy in Forge (auto-runs migrations)
4. ✅ Verify with `migrate:status`

### Your deployment script handles migrations automatically! 🎉

---

<div align="center">

**🗄️ Database Setup Complete!**

Your ExpenseSettle database is ready to use!

</div>
