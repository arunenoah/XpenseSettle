# ExpenseSettle - Implementation Guide

## Project Architecture Overview

This is a Laravel Blade-based expense sharing application with the following structure:

```
app/
├── Models/               # Eloquent models (User, Group, Expense, etc.)
├── Services/             # Business logic (GroupService, ExpenseService, etc.)
├── Http/
│   ├── Controllers/      # (To be created)
│   ├── Requests/         # Form request validation (To be created)
│   └── Middleware/       # Authentication & authorization (To be created)
├── Policies/             # Authorization policies (To be created)
└── Notifications/        # Notification classes (To be created)

database/
├── migrations/           # Database schema
├── factories/            # Model factories for testing
└── seeders/             # Database seeders

resources/
├── views/               # Blade templates (To be created)
├── css/
└── js/

routes/
├── web.php              # Web routes
└── api.php              # API routes (if needed)
```

## Completed Components

### 1. Database Migrations ✅
- Groups, GroupMembers, Expenses, ExpenseSplits, Payments, Comments, Attachments tables
- Proper foreign keys and indexes

### 2. Eloquent Models ✅
All models with relationships:
- User (hasMany groups, expenseSplits, payments, comments)
- Group (hasMany expenses, members)
- GroupMember (pivot with role)
- Expense (hasManythrough splits, hasMany comments)
- ExpenseSplit (belongsTo expense and user)
- Payment (belongsTo split)
- Comment (belongsTo expense and user)
- Attachment (morphMany to expenses, payments, comments)

### 3. Service Classes ✅
- **GroupService**: Create/update groups, manage members, calculate balances
- **ExpenseService**: Create/update expenses, handle split logic
- **PaymentService**: Mark payments, track payment status
- **AttachmentService**: Handle file uploads with validation
- **NotificationService**: Create in-database notifications

## Next Steps to Complete Implementation

### Step 1: Create Notification Migration
```bash
php artisan make:migration create_notifications_table
```

Add to migration:
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('type');
    $table->string('title');
    $table->text('message');
    $table->json('data')->nullable();
    $table->boolean('read')->default(false);
    $table->timestamps();
});
```

### Step 2: Create Authorization Policies
```bash
php artisan make:policy GroupPolicy --model=Group
php artisan make:policy ExpensePolicy --model=Expense
```

**GroupPolicy Example**:
```php
public function update(User $user, Group $group)
{
    return $group->isAdmin($user);
}

public function addMember(User $user, Group $group)
{
    return $group->isAdmin($user);
}
```

**ExpensePolicy Example**:
```php
public function update(User $user, Expense $expense)
{
    return $user->id === $expense->payer_id ||
           $expense->group->isAdmin($user);
}

public function delete(User $user, Expense $expense)
{
    return $user->id === $expense->payer_id ||
           $expense->group->isAdmin($user);
}
```

### Step 3: Create Form Requests (Validation)

**GroupRequest**:
```php
class StoreGroupRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'currency' => 'nullable|string|in:USD,EUR,GBP,INR',
        ];
    }
}
```

**ExpenseRequest**:
```php
class StoreExpenseRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'split_type' => 'required|in:equal,custom,percentage',
            'date' => 'required|date',
            'splits' => 'required_if:split_type,custom|array',
            'splits.*' => 'numeric|min:0',
        ];
    }
}
```

### Step 4: Create Controllers

**GroupController**:
```php
class GroupController extends Controller
{
    private GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function index()
    {
        $groups = auth()->user()->groups()->with('members')->paginate();
        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        return view('groups.create');
    }

    public function store(StoreGroupRequest $request)
    {
        $group = $this->groupService->createGroup(
            auth()->user(),
            $request->validated()
        );

        return redirect()->route('groups.show', $group)
                        ->with('success', 'Group created successfully');
    }

    public function show(Group $group)
    {
        $this->authorize('view', $group);

        $expenses = $group->expenses()
            ->with('payer', 'splits')
            ->latest()
            ->paginate();

        $balances = $this->groupService->getGroupBalance($group);

        return view('groups.show', compact('group', 'expenses', 'balances'));
    }

    public function edit(Group $group)
    {
        $this->authorize('update', $group);
        return view('groups.edit', compact('group'));
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $this->authorize('update', $group);

        $group = $this->groupService->updateGroup(
            $group,
            $request->validated()
        );

        return redirect()->route('groups.show', $group)
                        ->with('success', 'Group updated successfully');
    }

    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        $this->groupService->deleteGroup($group);

        return redirect()->route('groups.index')
                        ->with('success', 'Group deleted successfully');
    }

    public function addMember(Request $request, Group $group)
    {
        $this->authorize('addMember', $group);

        try {
            $this->groupService->addMember(
                $group,
                $request->email,
                $request->role ?? 'member'
            );

            return back()->with('success', 'Member added successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function removeMember(Group $group, User $user)
    {
        $this->authorize('update', $group);

        $this->groupService->removeMember($group, $user);

        return back()->with('success', 'Member removed successfully');
    }
}
```

### Step 5: Create Routes

In `routes/web.php`:
```php
Route::middleware('auth')->group(function () {
    Route::resource('groups', GroupController::class);
    Route::resource('groups.expenses', ExpenseController::class)->shallow();
    Route::resource('expenses.comments', CommentController::class)->shallow();

    Route::post('expenses/{expense}/payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])
        ->name('payments.mark-paid');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
});

Route::post('groups/{group}/members', [GroupController::class, 'addMember'])
    ->name('groups.add-member')
    ->middleware('auth');
```

### Step 6: Create Blade Views

**Main Layout** (`resources/views/layouts/app.blade.php`):
```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ExpenseSettle</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav><!-- Navigation --></nav>

    <main class="container">
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
```

**Groups Index** (`resources/views/groups/index.blade.php`):
```blade
@extends('layouts.app')

@section('content')
<div class="groups-container">
    <h1>My Groups</h1>
    <a href="{{ route('groups.create') }}" class="btn btn-primary">Create Group</a>

    @forelse($groups as $group)
        <div class="group-card">
            <h3><a href="{{ route('groups.show', $group) }}">{{ $group->name }}</a></h3>
            <p>{{ $group->members()->count() }} members</p>
            <p>{{ $group->expenses()->count() }} expenses</p>
        </div>
    @empty
        <p>No groups yet. <a href="{{ route('groups.create') }}">Create one</a></p>
    @endforelse

    {{ $groups->links() }}
</div>
@endsection
```

**Create Expense** (`resources/views/expenses/create.blade.php`):
```blade
@extends('layouts.app')

@section('content')
<form action="{{ route('groups.expenses.store', $group) }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" required>
        @error('title') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Amount</label>
        <input type="number" name="amount" step="0.01" class="form-control @error('amount') is-invalid @enderror" required>
        @error('amount') <span class="error">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label>Date</label>
        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}">
    </div>

    <div class="form-group">
        <label>Split Type</label>
        <select name="split_type" class="form-control">
            <option value="equal">Equal Split</option>
            <option value="custom">Custom Amounts</option>
            <option value="percentage">Percentage Split</option>
        </select>
    </div>

    <div id="splits-container"></div>

    <div class="form-group">
        <label>Receipt / Attachment</label>
        <input type="file" name="attachment" accept=".jpg,.png,.pdf">
    </div>

    <button type="submit" class="btn btn-primary">Create Expense</button>
</form>
@endsection
```

### Step 7: Create Dashboard View

**Dashboard** (`resources/views/dashboard.blade.php`):
```blade
@extends('layouts.app')

@section('content')
<div class="dashboard">
    <h1>Dashboard</h1>

    <div class="summary-cards">
        <div class="card">
            <h3>Total Owed</h3>
            <p>{{ $totalOwed }}</p>
        </div>
        <div class="card">
            <h3>Total Paid</h3>
            <p>{{ $totalPaid }}</p>
        </div>
        <div class="card">
            <h3>Pending Payments</h3>
            <p>{{ $pendingPayments }}</p>
        </div>
    </div>

    <div class="recent-expenses">
        <h2>Recent Expenses</h2>
        @foreach($recentExpenses as $expense)
            <div class="expense-item">
                <p>{{ $expense->title }} - {{ $expense->amount }}</p>
                <small>{{ $expense->group->name }}</small>
            </div>
        @endforeach
    </div>

    <div class="pending-payments">
        <h2>Your Pending Payments</h2>
        @foreach($pendingPaymentsList as $payment)
            <div class="payment-item">
                <p>{{ $payment->split->expense->title }}</p>
                <p>{{ $payment->split->share_amount }}</p>
                <form method="POST" action="{{ route('payments.mark-paid', [$payment->split->expense, $payment]) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Mark as Paid</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
```

### Step 8: Create DashboardController

```php
class DashboardController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $user = auth()->user();

        $stats = [];
        foreach ($user->groups as $group) {
            $groupStats = $this->paymentService->getPaymentStats($user, $group->id);
            $stats[$group->id] = $groupStats;
        }

        $pendingPayments = $this->paymentService->getPendingPaymentsForUser($user);

        $totalOwed = $pendingPayments->sum('split.share_amount');
        $totalPaid = $user->payments()->where('status', 'paid')->sum('split.share_amount');

        $recentExpenses = Expense::whereHas('group.members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->latest()->limit(5)->get();

        return view('dashboard', [
            'stats' => $stats,
            'totalOwed' => $totalOwed,
            'totalPaid' => $totalPaid,
            'pendingPayments' => $pendingPayments->count(),
            'pendingPaymentsList' => $pendingPayments,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
```

### Step 9: Create Export Functionality

```php
class ExportController extends Controller
{
    public function exportGroupCSV(Group $group)
    {
        $this->authorize('view', $group);

        $expenses = $group->expenses()->with('payer', 'splits')->get();

        $csv = "Date,Title,Amount,Payer,Split Type,Status\n";

        foreach ($expenses as $expense) {
            $csv .= implode(',', [
                $expense->date,
                $expense->title,
                $expense->amount,
                $expense->payer->name,
                $expense->split_type,
                $expense->status,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="group_' . $group->id . '.csv"',
        ]);
    }

    public function exportGroupPDF(Group $group)
    {
        $this->authorize('view', $group);

        $expenses = $group->expenses()->with('payer', 'splits')->get();
        $balances = (new GroupService())->getGroupBalance($group);

        // Use a PDF library like TCPDF or DomPDF
        $pdf = \PDF::loadView('exports.group-summary', [
            'group' => $group,
            'expenses' => $expenses,
            'balances' => $balances,
        ]);

        return $pdf->download('group_' . $group->id . '.pdf');
    }
}
```

### Step 10: Database Seeding

Create a seeder to test the application:

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);

        $group = Group::factory()->create(['created_by' => $user1->id]);

        GroupMember::create(['group_id' => $group->id, 'user_id' => $user1->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $group->id, 'user_id' => $user2->id, 'role' => 'member']);

        $expense = Expense::factory()->create(['group_id' => $group->id, 'payer_id' => $user1->id]);

        ExpenseSplit::create(['expense_id' => $expense->id, 'user_id' => $user1->id, 'share_amount' => 50]);
        ExpenseSplit::create(['expense_id' => $expense->id, 'user_id' => $user2->id, 'share_amount' => 50]);
    }
}
```

## Environment Setup

1. Create MySQL database:
```sql
CREATE DATABASE expensesettle;
```

2. Update `.env`:
```
DB_DATABASE=expensesettle
DB_USERNAME=root
DB_PASSWORD=
```

3. Run migrations:
```bash
php artisan migrate
```

4. Create symbolic link for storage:
```bash
php artisan storage:link
```

5. Start development server:
```bash
php artisan serve
```

## Testing

Create tests for your services:

```bash
php artisan make:test Services/GroupServiceTest
php artisan make:test Services/ExpenseServiceTest
```

## Security Considerations

1. **Authorization**: All routes protected with Gate/Policy checks
2. **File Validation**: MIME type and size validation in AttachmentService
3. **SQL Injection**: Use Eloquent ORM (parameterized queries)
4. **CSRF Protection**: Enabled by default in Laravel Blade
5. **Mass Assignment**: Fillable properties defined in models
6. **Data Validation**: Form Request classes for all input

## Performance Optimizations

1. **Eager Loading**: Use `with()` to avoid N+1 queries
2. **Pagination**: Implemented in list views
3. **Indexing**: Foreign keys are indexed
4. **Caching**: Cache group balances for expensive calculations

## API Future Enhancement

If you need to add a REST API later, create:
- API Controllers (inheritance from base ApiController)
- API Resources for JSON formatting
- API Middleware for token authentication (Sanctum)
