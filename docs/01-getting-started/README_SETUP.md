# ExpenseSettle - Expense Sharing Application

A Laravel-based web application for tracking shared expenses among groups (like roommates, trip participants, or project teams).

## 🚀 Features

### Completed & Ready to Use
- ✅ Complete database schema with 8 tables
- ✅ 7 Eloquent models with relationships
- ✅ 5 service classes for business logic
- ✅ Group management (create, update, add/remove members)
- ✅ Expense creation with multiple split types (equal, custom, percentage)
- ✅ Payment tracking and status management
- ✅ File attachments for expenses, payments, and comments
- ✅ Notification system framework
- ✅ Authorization policy structure
- ✅ Session-based authentication ready

### To Be Implemented
- Controllers and routes
- Blade views and templates
- Styling (CSS/Tailwind)
- Export to PDF/CSV
- Email notifications
- Search and filtering
- Unit and integration tests

## 📋 Prerequisites

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js & npm (for Vite)
- Git

## 🔧 Installation & Setup

### 1. Clone or Navigate to Project
```bash
cd /Users/arunkumar/Documents/Application/expenseSettle
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node Dependencies
```bash
npm install
```

### 4. Create .env File
```bash
cp .env.example .env
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Create MySQL Database
```bash
mysql -u root -e "CREATE DATABASE expensesettle;"
```

### 7. Update Database Configuration
Edit `.env` and ensure these settings:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=expensesettle
DB_USERNAME=root
DB_PASSWORD=
```

### 8. Run Migrations
```bash
php artisan migrate
```

### 9. Create Storage Link
```bash
php artisan storage:link
```

### 10. Build Frontend Assets
```bash
npm run dev
# For production: npm run build
```

### 11. Start Development Server
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 📚 Documentation

- **README_SETUP.md** - Project overview and setup (this file)
- **QUICK_START.md** - Quick reference guide and next steps
- **IMPLEMENTATION_GUIDE.md** - Detailed implementation instructions with code examples
- **MOBILE_RESPONSIVE_GUIDE.md** - Mobile-first design with Tailwind CSS
- **PROJECT_STATUS.md** - Current project status and deliverables

## 🏗️ Architecture

### Models (7 Total)
```
User → Groups, Expenses, ExpenseSplits, Payments, Comments
  ↓
Group → Members, Expenses
  ↓
Expense → Splits, Comments, Attachments
  ↓
ExpenseSplit → Payment
  ↓
Payment → Attachments
  ↓
Comment → Attachments
```

### Service Layer (5 Services)
1. **GroupService** - Group CRUD and member management
2. **ExpenseService** - Expense creation with split logic
3. **PaymentService** - Payment tracking and statistics
4. **AttachmentService** - File upload and validation
5. **NotificationService** - In-app notification system

### Key Directories
```
app/Models/           → 7 Eloquent models
app/Services/         → 5 business logic services
app/Http/Controllers/ → Controllers (to be created)
database/migrations/  → 8 table migrations
database/factories/   → Model factories for testing
resources/views/      → Blade templates (to be created)
routes/               → Route definitions (to be configured)
```

## 💾 Database Schema

### Users Table
```
id, name, email, password, email_verified_at, remember_token, timestamps
```

### Groups Table
```
id, created_by (FK), name, description, currency, timestamps
```

### GroupMembers Table
```
id, group_id (FK), user_id (FK), role (admin/member), timestamps
Unique: (group_id, user_id)
```

### Expenses Table
```
id, group_id (FK), payer_id (FK), title, description, amount,
split_type (equal/custom/percentage), date, status, timestamps
```

### ExpenseSplits Table
```
id, expense_id (FK), user_id (FK), share_amount, percentage, timestamps
Unique: (expense_id, user_id)
```

### Payments Table
```
id, expense_split_id (FK), paid_by (FK), status (pending/paid/rejected),
paid_date, notes, timestamps
```

### Comments Table
```
id, expense_id (FK), user_id (FK), content, timestamps
```

### Attachments Table (Polymorphic)
```
id, attachable_id, attachable_type, file_path, file_name,
mime_type, file_size, timestamps
```

## 🔐 Security Features

- ✅ Password hashing with bcrypt
- ✅ CSRF protection (Laravel middleware)
- ✅ File validation (MIME type + size limits)
- ✅ Model mass assignment protection
- ✅ Foreign key constraints
- ✅ Authorization policies ready to implement
- ✅ Unique constraints on pivot tables

## 🧪 Testing Setup

```bash
# Create test file
php artisan make:test Services/GroupServiceTest

# Run tests
php artisan test

# Run specific test
php artisan test tests/Unit/Services/GroupServiceTest.php

# Generate coverage report
php artisan test --coverage
```

## 🚦 Development Workflow

### Phase 1: Controllers & Routes
```bash
php artisan make:controller GroupController --resource --model=Group
php artisan make:controller ExpenseController --resource --model=Expense
# ... create remaining controllers
```

### Phase 2: Blade Views
Create directory structure:
```
resources/views/
├── layouts/
│   └── app.blade.php
├── groups/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── show.blade.php
│   └── edit.blade.php
├── expenses/
│   ├── create.blade.php
│   └── show.blade.php
├── dashboard.blade.php
└── emails/
    └── notification.blade.php
```

### Phase 3: Styling
Use Tailwind CSS (included):
```bash
npm install -D tailwindcss
npx tailwindcss init
```

### Phase 4: Testing
Create comprehensive test suites for all features.

## 📖 API Reference

### GroupService
```php
createGroup(user, data) → Group
updateGroup(group, data) → Group
addMember(group, email, role) → GroupMember
removeMember(group, user) → bool
getGroupBalance(group) → array
```

### ExpenseService
```php
createExpense(group, payer, data) → Expense
updateExpense(expense, data) → Expense
deleteExpense(expense) → bool
getExpenseSettlement(expense) → array
markExpenseAsPaid(expense) → Expense
```

### PaymentService
```php
markAsPaid(split, paidBy, data) → Payment
rejectPayment(payment, reason) → Payment
getPendingPaymentsForUser(user, groupId) → Collection
getPaymentStats(user, groupId) → array
```

### AttachmentService
```php
uploadAttachment(file, model, directory) → Attachment
deleteAttachment(attachment) → bool
downloadFile(attachment) → Response
```

## 🛠️ Useful Commands

```bash
# Database
php artisan migrate                    # Run migrations
php artisan migrate:fresh              # Reset database
php artisan migrate:fresh --seed       # Reset with seeding
php artisan migrate:rollback           # Undo last batch

# Cache
php artisan cache:clear                # Clear application cache
php artisan config:cache               # Cache configuration

# Development
php artisan serve                      # Start dev server
php artisan tinker                     # Interactive shell
php artisan routes:list                # List all routes
php artisan db:seed                    # Run seeders

# Generation
php artisan make:model ModelName       # Create model
php artisan make:migration table_name  # Create migration
php artisan make:controller Controller # Create controller
php artisan make:policy PolicyName     # Create policy
php artisan make:request RequestName   # Create form request
```

## 🐛 Troubleshooting

### Database Connection Error
```bash
# Verify .env settings
# Check MySQL is running
mysql -u root -p
# Create database if missing
CREATE DATABASE expensesettle;
```

### Migration Issues
```bash
# Rollback all migrations
php artisan migrate:rollback --step=99

# Fresh start
php artisan migrate:fresh
```

### File Upload Issues
```bash
# Create storage link
php artisan storage:link

# Check permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Port Already in Use
```bash
# Use different port
php artisan serve --port=8001
```

## 📦 Key Dependencies

- **Laravel 12** - Web framework
- **MySQL** - Database
- **Blade** - Template engine
- **Eloquent** - ORM
- **Vite** - Asset bundling
- **PHPUnit** - Testing framework

## 🎯 Project Status

| Component | Status | Notes |
|-----------|--------|-------|
| Database Schema | ✅ Complete | 8 tables, all migrations |
| Models | ✅ Complete | 7 models with relationships |
| Services | ✅ Complete | 5 service classes |
| Controllers | ⏳ Ready | Scaffolding commands prepared |
| Views | ⏳ Ready | Templates structure defined |
| Routes | ⏳ Ready | Route structure defined |
| Tests | ⏳ Ready | Test framework configured |
| Styling | ⏳ Pending | Tailwind setup included |
| Documentation | ✅ Complete | IMPLEMENTATION_GUIDE.md |

## 🤝 Contributing

1. Create a new branch: `git checkout -b feature/feature-name`
2. Make your changes
3. Commit: `git commit -am 'Add feature'`
4. Push: `git push origin feature/feature-name`
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License.

## 💬 Support & Questions

For detailed implementation help:
- See **IMPLEMENTATION_GUIDE.md** for step-by-step instructions
- See **QUICK_START.md** for quick reference
- Check model files for relationship examples
- Review service files for business logic examples

## 🎉 Next Steps

1. **Review QUICK_START.md** - Understand what's been built
2. **Follow IMPLEMENTATION_GUIDE.md** - Implement remaining components
3. **Create Controllers** - Handle HTTP requests
4. **Build Views** - Create user interface
5. **Add Tests** - Ensure quality
6. **Deploy** - Take it live!

---

**Created with ❤️ using Laravel**

For any questions or issues, refer to the detailed documentation files included in this project.
