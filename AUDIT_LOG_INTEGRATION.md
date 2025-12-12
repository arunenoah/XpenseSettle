# Audit Logging System - Integration Guide

## Overview

A comprehensive audit logging system has been added to track all user actions in ExpenseSettle. Only group admins can view audit logs for their assigned groups.

## What Gets Logged

### Authentication Events
- ✅ User login
- ✅ User logout

### Group Management
- ✅ Create group
- ✅ Update group details
- ✅ Delete group

### Member Management
- ✅ Add user member
- ✅ Remove member
- ✅ Update member role (member/admin)
- ✅ Update member family count

### Contact Management
- ✅ Add contact
- ✅ Update contact details

### Expense Management
- ✅ Create expense
- ✅ Update expense
- ✅ Delete expense

### Payment Processing
- ✅ Mark payment as paid
- ✅ Approve payment
- ✅ Reject payment

## Database Structure

```
audit_logs table:
- id (PK)
- user_id (FK) - Who performed the action
- group_id (FK) - Which group (nullable for auth logs)
- action (string) - create_group, add_member, mark_paid, etc.
- entity_type (string) - Group, Expense, Payment, etc.
- entity_id (int) - ID of affected entity
- description (text) - Human-readable description
- changes (JSON) - Old/new values for updates
- ip_address (string) - User's IP
- user_agent (string) - Browser/app info
- status (string) - 'success' or 'failed'
- error_message (text) - If status is 'failed'
- timestamps
```

## Integration Steps

### 1. Run Migration

```bash
php artisan migrate
```

### 2. AuthController - Add Login/Logout Logging

```php
<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
// ... other imports

class AuthController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($validated)) {
            $user = Auth::user();

            // Log successful login
            $this->auditService->logLogin($user);

            // ... rest of login logic
        }

        // Log failed login attempt
        $this->auditService->logFailed(
            'login',
            'User',
            'Failed login attempt for email: ' . $validated['email'],
            'Invalid credentials'
        );
        // ... error handling
    }

    public function logout(Request $request)
    {
        $user = auth()->user();

        // Log logout
        $this->auditService->logLogout($user);

        // ... rest of logout logic
    }
}
```

### 3. GroupController - Add Group Operation Logging

```php
<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
// ... other imports

class GroupController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // ... other validations
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'created_by' => auth()->id(),
            // ... other fields
        ]);

        // Log group creation
        $this->auditService->logGroupCreated($group);

        return redirect()->route('groups.show', $group);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // ... validations
        ]);

        // Track changes
        $changes = [];
        if ($group->name !== $validated['name']) {
            $changes['name'] = [
                'from' => $group->name,
                'to' => $validated['name'],
            ];
        }

        $group->update($validated);

        // Log update if there were changes
        if (!empty($changes)) {
            $this->auditService->logGroupUpdated($group, $changes);
        }

        return redirect()->back()->with('success', 'Group updated');
    }

    public function destroy(Group $group)
    {
        // Check authorization
        if (!$group->isAdmin(auth()->user())) {
            abort(403);
        }

        $groupName = $group->name;
        $groupId = $group->id;

        $group->delete();

        // Log deletion
        $this->auditService->logGroupDeleted($group);

        return redirect()->route('groups.index');
    }

    public function addMember(Request $request, Group $group)
    {
        // ... existing validation and logic

        $member = $this->memberService->addUserMember($group, $userId);

        // Log member addition
        $this->auditService->logMemberAdded($group, $member);

        return redirect()->back()->with('success', 'Member added');
    }

    public function removeMember(Group $group, GroupMember $member)
    {
        // ... authorization checks

        $this->auditService->logMemberRemoved($group, $member);

        $member->delete();

        return redirect()->back();
    }

    public function addContact(Request $request, Group $group)
    {
        // ... validation

        $contact = Contact::create([
            'group_id' => $group->id,
            'name' => $validated['contact_name'],
            // ... other fields
        ]);

        // Log contact addition
        $this->auditService->logContactAdded($group, $contact);

        return redirect()->back();
    }
}
```

### 4. ExpenseController - Add Expense Operation Logging

```php
<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
// ... other imports

class ExpenseController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function store(Request $request, Group $group)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            // ... other validations
        ]);

        $expense = Expense::create([
            'group_id' => $group->id,
            'payer_id' => auth()->id(),
            // ... other fields
        ]);

        // Log expense creation
        $this->auditService->logExpenseCreated($group, $expense);

        return redirect()->back();
    }

    public function update(Request $request, Group $group, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            // ... validations
        ]);

        $changes = [];
        if ($expense->title !== $validated['title']) {
            $changes['title'] = ['from' => $expense->title, 'to' => $validated['title']];
        }
        if ($expense->amount != $validated['amount']) {
            $changes['amount'] = ['from' => $expense->amount, 'to' => $validated['amount']];
        }

        $expense->update($validated);

        if (!empty($changes)) {
            $this->auditService->logExpenseUpdated($group, $expense, $changes);
        }

        return redirect()->back();
    }

    public function destroy(Group $group, Expense $expense)
    {
        $expenseTitle = $expense->title;
        $expense->delete();

        $this->auditService->logExpenseDeleted($group, $expenseTitle);

        return redirect()->back();
    }
}
```

### 5. PaymentController - Add Payment Operation Logging

```php
<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
// ... other imports

class PaymentController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function markPaid(Request $request, ExpenseSplit $split)
    {
        // ... existing logic

        $payment = $this->paymentService->markAsPaid($split, auth()->user(), $validated);

        $group = $split->expense->group;
        $this->auditService->logPaymentMarked($group, $payment, $split->expense);

        return back()->with('success', 'Payment marked as paid');
    }

    public function approve(Request $request, Payment $payment)
    {
        // ... authorization

        $payment->update(['status' => 'approved']);

        $group = $payment->split->expense->group;
        $this->auditService->logPaymentApproved($group, $payment, $payment->split->expense);

        return back()->with('success', 'Payment approved');
    }

    public function reject(Request $request, Payment $payment)
    {
        // ... authorization

        $this->paymentService->rejectPayment($payment, $validated['reason']);

        $group = $payment->split->expense->group;
        $this->auditService->logPaymentRejected(
            $group,
            $payment,
            $payment->split->expense,
            $validated['reason']
        );

        return back()->with('success', 'Payment rejected');
    }
}
```

## Accessing Audit Logs

### For Group Admins

Visit: `/groups/{group_id}/audit-logs`

Features:
- View all actions in chronological order
- Filter by action type (login, create_expense, mark_paid, etc.)
- See user, timestamp, IP address, and status
- Export as CSV for reporting

### Available Filters

- Login
- Logout
- Create Group
- Update Group
- Add Member
- Remove Member
- Add Contact
- Create Expense
- Update Expense
- Delete Expense
- Mark Paid
- Approve Payment
- Reject Payment

### CSV Export

Click "Export CSV" to download all logs for:
- Date/Time
- User
- Action
- Entity Type
- Description
- IP Address
- Status

## Security Features

1. **Authorization**: Only group admins can view their group's audit logs
2. **IP Tracking**: All actions logged with user's IP address
3. **User Agent**: Browser/app information captured
4. **Immutable Logs**: Audit logs are append-only (never deleted)
5. **Soft Deletes**: Deleted items are tracked but preserved

## Example Audit Log Entries

```
2025-12-11 10:30:15 | Arun | login | User | Arun logged in | 192.168.1.1 | ✓ Success

2025-12-11 10:31:22 | Arun | create_expense | Expense | Expense 'Hotel' (₹5000) created in group 'Dubbo trip' | 192.168.1.1 | ✓ Success

2025-12-11 10:32:45 | Velu | mark_paid | Payment | Velu marked payment of ₹2500 as paid for 'Hotel' in group 'Dubbo trip' | 192.168.1.5 | ✓ Success

2025-12-11 10:33:10 | Arun | approve_payment | Payment | Payment of ₹2500 from Velu for 'Hotel' approved in group 'Dubbo trip' | 192.168.1.1 | ✓ Success

2025-12-11 10:35:50 | Arun | add_member | GroupMember | Member 'Karthick' added to group 'Dubbo trip' | 192.168.1.1 | ✓ Success
```

## Architecture

```
User Action
    ↓
Controller (groupPaymentHistory, store, update, destroy, etc.)
    ↓
AuditService->log*() (logLogin, logExpenseCreated, etc.)
    ↓
AuditLog Model
    ↓
audit_logs table
    ↓
Admin Views (audit-logs/group.blade.php)
    ↓
Group Admin Dashboard
```

## Performance Considerations

- Audit logs are indexed by group_id and created_at for fast querying
- Pagination: 50 logs per page by default
- Consider archiving old logs after 12 months

## Privacy & Compliance

- ✅ User action tracking
- ✅ Change history (before/after values)
- ✅ IP address logging
- ✅ Timestamp for all actions
- ✅ Success/failure status
- ✅ Group-level access control

## Next Steps

1. Run `php artisan migrate` to create the audit_logs table
2. Inject AuditService into your controllers
3. Add audit logging calls to relevant methods
4. Test by visiting `/groups/{group_id}/audit-logs`
5. Verify only group admins can access their group's logs
