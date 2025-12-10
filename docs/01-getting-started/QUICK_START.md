# ExpenseSettle - Quick Start Guide

## What's Been Done So Far

✅ **Project Structure**: Laravel 12 project initialized
✅ **Database Design**: Complete schema with 8 tables (migrations ready)
✅ **Eloquent Models**: All 7 models with relationships defined
✅ **Business Logic**: 5 service classes handling core functionality
  - GroupService: Group management
  - ExpenseService: Expense & split creation
  - PaymentService: Payment tracking
  - AttachmentService: File handling
  - NotificationService: Event notifications
✅ **Environment**: Configured for MySQL local development

## Complete Implementation Checklist

### Phase 1: Core Backend (Foundation)
- [x] Migrations & Models
- [x] Services & Business Logic
- [ ] Authorization Policies (see IMPLEMENTATION_GUIDE.md Step 2)
- [ ] Form Requests/Validation (see IMPLEMENTATION_GUIDE.md Step 3)
- [ ] Controllers (see IMPLEMENTATION_GUIDE.md Step 4)
- [ ] Routes (see IMPLEMENTATION_GUIDE.md Step 5)
- [ ] Notification System

### Phase 2: Frontend (User Interface)
- [ ] Create Blade templates
- [ ] Dashboard view
- [ ] Group management views
- [ ] Expense management views
- [ ] Payment tracking views
- [ ] CSS/Styling (Tailwind or Bootstrap)

### Phase 3: Features
- [ ] File upload with preview
- [ ] Comments on expenses
- [ ] Payment proof upload
- [ ] Export to CSV/PDF
- [ ] Email notifications (optional)
- [ ] Search and filtering

### Phase 4: Testing & Optimization
- [ ] Unit tests for services
- [ ] Feature tests for controllers
- [ ] Integration tests
- [ ] Performance optimization
- [ ] Documentation

## Running the Application

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (for Vite)

### Setup Commands

```bash
# 1. Install dependencies
composer install
npm install

# 2. Create MySQL database
mysql -u root -e "CREATE DATABASE expensesettle;"

# 3. Update .env (already configured for MySQL)
# Check DB_DATABASE=expensesettle in .env

# 4. Run migrations
php artisan migrate

# 5. Create storage link for file uploads
php artisan storage:link

# 6. Build frontend assets
npm run dev

# 7. Start development server
php artisan serve
```

Then visit: `http://localhost:8000`

### Create Test User

```bash
php artisan tinker

# In tinker shell:
>>> User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => Hash::make('password')])
>>> User::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => Hash::make('password')])
```

## Project Structure Quick Reference

```
📁 app/
├── Models/                    (All 7 models with relationships)
├── Services/                  (5 service classes)
│   ├── GroupService.php       ✅
│   ├── ExpenseService.php     ✅
│   ├── PaymentService.php     ✅
│   ├── AttachmentService.php  ✅
│   └── NotificationService.php ✅
├── Http/Controllers/          (To be created)
├── Http/Requests/             (To be created)
└── Policies/                  (To be created)

📁 database/
├── migrations/                (All migrations configured)
├── factories/                 (7 model factories created)
└── seeders/                   (To be created)

📁 resources/views/            (Blade templates - to be created)
├── layouts/
├── groups/
├── expenses/
├── dashboard/
└── emails/

📁 routes/
├── web.php                    (To be configured)
└── api.php                    (Optional)
```

## Key Files Already Created

### Models (app/Models/)
- User.php - with relationships
- Group.php - group management
- GroupMember.php - pivot model
- Expense.php - expense tracking
- ExpenseSplit.php - split tracking
- Payment.php - payment status
- Comment.php - comments
- Attachment.php - file management

### Services (app/Services/)
- GroupService.php - CRUD operations, balance calculation
- ExpenseService.php - create/update/delete, split logic
- PaymentService.php - mark paid, statistics
- AttachmentService.php - upload validation, storage
- NotificationService.php - database notifications

### Database
All migrations are ready in `database/migrations/`:
- create_users_table
- create_groups_table
- create_group_members_table
- create_expenses_table
- create_expense_splits_table
- create_payments_table
- create_comments_table
- create_attachments_table

## Next Immediate Steps (In Order)

### 1. Create Notification Table
```bash
php artisan make:migration create_notifications_table
```

### 2. Create Policies
```bash
php artisan make:policy GroupPolicy --model=Group
php artisan make:policy ExpensePolicy --model=Expense
```

### 3. Create Requests
```bash
php artisan make:request StoreGroupRequest
php artisan make:request StoreExpenseRequest
```

### 4. Create Controllers
```bash
php artisan make:controller GroupController --model=Group --resource
php artisan make:controller ExpenseController --model=Expense --resource
php artisan make:controller DashboardController
php artisan make:controller PaymentController
php artisan make:controller AttachmentController
php artisan make:controller CommentController
```

### 5. Configure Routes
Edit `routes/web.php` and add resource routes

### 6. Create Views
Create Blade templates in `resources/views/`

### 7. Setup Authentication
```bash
php artisan make:auth
# OR use Laravel Breeze for modern scaffolding
composer require laravel/breeze --dev
php artisan breeze:install blade
```

## Common Commands During Development

```bash
# Clear cache
php artisan cache:clear

# Run migrations fresh
php artisan migrate:fresh --seed

# Tinker shell (interactive)
php artisan tinker

# Check routes
php artisan route:list

# Generate documentation
php artisan route:list --format=markdown > ROUTES.md

# Create factory
php artisan make:factory GroupFactory

# Run tests
php artisan test
```

## Database Relationships Summary

```
User
├── hasMany Groups (created_by)
├── belongsToMany Groups (group_members)
├── hasMany Expenses (payer_id)
├── hasMany ExpenseSplits
├── hasMany Payments (paid_by)
└── hasMany Comments

Group
├── belongsTo User (creator)
├── belongsToMany Users (members)
├── hasMany GroupMembers
└── hasMany Expenses

Expense
├── belongsTo Group
├── belongsTo User (payer)
├── hasMany ExpenseSplits
├── hasMany Comments
└── morphMany Attachments

ExpenseSplit
├── belongsTo Expense
├── belongsTo User
├── hasOne Payment
└── hasMany Attachments (polymorphic)

Payment
├── belongsTo ExpenseSplit
├── belongsTo User (paid_by)
└── morphMany Attachments

Comment
├── belongsTo Expense
├── belongsTo User
└── morphMany Attachments

GroupMember
├── belongsTo Group
└── belongsTo User

Attachment
├── morphTo (Expense, Payment, Comment)
```

## Key Features Already Available

### GroupService Methods
- createGroup(user, data)
- updateGroup(group, data)
- addMember(group, email, role)
- removeMember(group, user)
- updateMemberRole(group, user, role)
- deleteGroup(group)
- getGroupBalance(group)

### ExpenseService Methods
- createExpense(group, payer, data)
- updateExpense(expense, data)
- deleteExpense(expense)
- getExpenseSettlement(expense)
- markExpenseAsPaid(expense)

### PaymentService Methods
- markAsPaid(split, paidBy, data)
- rejectPayment(payment, reason)
- createPaymentRecord(split)
- getPendingPaymentsForUser(user, groupId)
- getPaymentStats(user, groupId)

### AttachmentService Methods
- uploadAttachment(file, model, directory)
- deleteAttachment(attachment)
- getDownloadUrl(attachment)
- downloadFile(attachment)

## Security Features Already Implemented

✅ Password hashing (Eloquent casts)
✅ CSRF protection (Laravel default)
✅ File validation (MIME type, size limit)
✅ Model mass assignment protection (fillable arrays)
✅ Foreign key constraints in database
✅ Unique constraints on pivots

## Performance Considerations

✅ Eager loading setup in services (with() clauses)
✅ Proper indexing in migrations
✅ Decimal types for money (not float)
✅ Pagination ready in views
✅ Query optimization via services

## Debugging Tips

### Check Database
```bash
php artisan tinker
>>> DB::table('groups')->get()
>>> User::with('groups')->first()
```

### Check Service Logic
```bash
# In tinker:
>>> $service = new \App\Services\GroupService();
>>> $group = Group::first();
>>> $service->getGroupBalance($group)
```

### Enable Debug Mode
In `.env`: `APP_DEBUG=true` (already set)

## Environment Configuration

### Local Development (.env configured)
```
APP_NAME=ExpenseSettle
APP_DEBUG=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=expensesettle
FILESYSTEM_DISK=local
```

### File Upload Configuration
Max file size: 10MB
Allowed types: JPG, PNG, GIF, PDF
Storage location: `storage/app/attachments/`

## Next Phase: Create Controllers

The most important next step is to create the controllers using the example code in IMPLEMENTATION_GUIDE.md:

1. GroupController (CRUD operations)
2. ExpenseController (CRUD + splits)
3. DashboardController (Summary & stats)
4. PaymentController (Payment actions)
5. CommentController (Comments)
6. AttachmentController (File uploads)

Once controllers are created, add them to `routes/web.php` and create the corresponding Blade templates.

## Support

For detailed implementation:
- See IMPLEMENTATION_GUIDE.md for full code examples
- Check model relationships in app/Models/
- Review service methods for business logic
- Use `php artisan tinker` to test code

Happy coding! 🚀
