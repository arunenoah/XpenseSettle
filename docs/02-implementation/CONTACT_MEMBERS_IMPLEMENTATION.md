# Contact Members Implementation Guide

## Overview

This guide explains how to use the **Contact Members** system that allows both regular users and non-login contacts to be members of groups and split expenses.

## Database Schema

The system supports **dual membership types** without deleting any existing data:

### group_members table
```
- user_id (nullable) - For active users who can log in
- contact_id (nullable) - For contacts who cannot log in
- role - 'member' or 'admin'
- family_count - Number of family members
```

### expense_splits table
```
- user_id (nullable) - For user splits
- contact_id (nullable) - For contact splits
- share_amount - Amount this member owes/is owed
- percentage - Percentage if split by percentage
```

**Key Feature:** Either `user_id` OR `contact_id` must be set (not both, not neither).

---

## How to Use

### 1. Add a User as Group Member (Existing)

```php
use App\Services\GroupMemberService;

$service = new GroupMemberService();
$group = Group::find(1);
$userId = 5;

// Add existing user to group
$service->addUserMember($group, $userId, 'member');
```

### 2. Add a Contact as Group Member (New)

```php
$service = new GroupMemberService();
$group = Group::find(1);

// Add a contact (non-login member)
$service->addContactMember(
    group: $group,
    name: 'Subbiah',
    email: 'subbiah@example.com',
    phone: '9876543210',
    role: 'member'
);
```

### 3. Create Expense Paid by User

```php
$group = Group::find(1);
$userId = auth()->id(); // Current logged-in user

$expense = $group->expenses()->create([
    'payer_id' => $userId,
    'title' => 'Groceries',
    'amount' => 300,
    'date' => now(),
    'split_type' => 'equal',
    'status' => 'active',
]);
```

### 4. Split Between User and Contact

```php
// Get all members (both users and contacts)
$members = $group->allMembers()->get();

// Split expense equally among members
$totalAmount = $expense->amount;
$splitAmount = $totalAmount / $members->count();

foreach ($members as $member) {
    if ($member->isActiveUser()) {
        // Split for a user
        $expense->splits()->create([
            'user_id' => $member->user_id,
            'contact_id' => null,
            'share_amount' => $splitAmount,
            'percentage' => (100 / $members->count()),
        ]);
    } else {
        // Split for a contact
        $expense->splits()->create([
            'user_id' => null,
            'contact_id' => $member->contact_id,
            'share_amount' => $splitAmount,
            'percentage' => (100 / $members->count()),
        ]);
    }
}
```

### 5. Split by Custom Amount

```php
// User split
$expense->splits()->create([
    'user_id' => 1,
    'contact_id' => null,
    'share_amount' => 150.00,
]);

// Contact split
$expense->splits()->create([
    'user_id' => null,
    'contact_id' => 2,
    'share_amount' => 150.00,
]);
```

---

## Model Methods

### ExpenseSplit Methods

```php
$split = ExpenseSplit::find(1);

// Get the member (User or Contact)
$member = $split->getMember();  // Returns User or Contact object

// Get member name
$name = $split->getMemberName(); // Returns string name

// Get the user relationship
$user = $split->user;  // Returns User or null

// Get the contact relationship
$contact = $split->contact;  // Returns Contact or null
```

### GroupMember Methods

```php
$member = GroupMember::find(1);

// Get the member (User or Contact)
$member_obj = $member->getMember();

// Get member name
$name = $member->getMemberName();

// Check member type
if ($member->isActiveUser()) {
    // This is a user who can log in
}

if ($member->isContact()) {
    // This is a contact without login
}
```

### Group Methods

```php
$group = Group::find(1);

// Get all members with relationships loaded
$allMembers = $group->allMembers()->get();

// Iterate with both types
foreach ($allMembers as $member) {
    echo $member->getMemberName(); // Works for both user and contact
}

// Get only contacts
$contacts = $group->contacts;

// Get only users (using the existing relationship)
$users = $group->members;
```

---

## Controller Examples

### Adding Members

```php
class GroupMemberController extends Controller
{
    public function addMember(Group $group, Request $request)
    {
        $service = new GroupMemberService();

        if ($request->has('user_id')) {
            // Add existing user
            $service->addUserMember(
                $group,
                $request->user_id,
                $request->role ?? 'member'
            );
        } else {
            // Add new contact
            $service->addContactMember(
                $group,
                $request->name,
                $request->email,
                $request->phone,
                $request->role ?? 'member'
            );
        }

        return response()->json(['success' => true]);
    }
}
```

### Creating Expense with Splits

```php
class ExpenseController extends Controller
{
    public function store(Group $group, Request $request)
    {
        $expense = $group->expenses()->create([
            'payer_id' => auth()->id(),
            'title' => $request->title,
            'amount' => $request->amount,
            'date' => $request->date,
        ]);

        // Add splits for each member
        foreach ($request->splits as $split) {
            $expense->splits()->create([
                'user_id' => $split['user_id'] ?? null,
                'contact_id' => $split['contact_id'] ?? null,
                'share_amount' => $split['amount'],
                'percentage' => $split['percentage'] ?? null,
            ]);
        }

        return response()->json($expense->load('splits'));
    }
}
```

### Displaying Members for Splitting

```php
class GroupController extends Controller
{
    public function show(Group $group)
    {
        $service = new GroupMemberService();

        return response()->json([
            'group' => $group,
            'members' => $service->getSplittableMembers($group),
        ]);
    }
}
```

---

## Important Rules

### Creating Splits

✅ **Correct:**
```php
// User split (contact_id is null)
['user_id' => 1, 'contact_id' => null, 'share_amount' => 100]

// Contact split (user_id is null)
['user_id' => null, 'contact_id' => 2, 'share_amount' => 100]
```

❌ **Incorrect:**
```php
// Both set - violates constraint
['user_id' => 1, 'contact_id' => 2, 'share_amount' => 100]

// Both null - violates NOT NULL requirement
['user_id' => null, 'contact_id' => null, 'share_amount' => 100]
```

### Adding Members

✅ **Correct:**
```php
// User member (user_id set, contact_id null)
['group_id' => 1, 'user_id' => 5, 'contact_id' => null]

// Contact member (contact_id set, user_id null)
['group_id' => 1, 'user_id' => null, 'contact_id' => 3]
```

❌ **Incorrect:**
```php
// Both set
['group_id' => 1, 'user_id' => 5, 'contact_id' => 3]

// Both null
['group_id' => 1, 'user_id' => null, 'contact_id' => null]
```

---

## Data Recovery

If you need to restore existing user-based data:

1. **User splits remain untouched** - `user_id` is always preserved
2. **New contact splits** - Use `contact_id` field
3. **Gradual migration** - You can mix user and contact members in the same group

---

## Service Methods Reference

```php
$service = new GroupMemberService();

// Add members
$service->addUserMember($group, $userId, $role);
$service->addContactMember($group, $name, $email, $phone, $role);

// Remove members
$service->removeMember($group, $userId);
$service->removeMember($group, contactId: $contactId);

// Get all splittable members (formatted for UI)
$members = $service->getSplittableMembers($group);

// Get single member
$member = $service->getMember($groupMemberId);

// Update role
$service->updateMemberRole($groupMemberId, 'admin');

// Contact management
$exists = $service->contactExistsInGroup($group, $email);
$contact = $service->getContactByEmail($group, $email);
$service->updateContact($contactId, ['name' => 'New Name']);
```

---

## Migration Path (Future)

When adding this to existing groups with users:

```php
// Existing user splits work as-is
// No changes needed to existing data

// New contacts added via:
$service->addContactMember($group, 'Name', 'email@example.com');

// Mix users and contacts in same expense:
$expense->splits()->create(['user_id' => 1, ...]);      // User
$expense->splits()->create(['contact_id' => 1, ...]);   // Contact
```

---

## No Data Loss Guarantee

✅ **All existing user-based data is preserved**
- User IDs remain in `user_id` column
- User splits work exactly as before
- No schema modifications to user relationships

✅ **Safe to deploy**
- Contact fields are nullable
- Contact functionality is purely additive
- Can rollback without affecting user data

---

## Testing

```php
// Test user split
$expense->splits()->create([
    'user_id' => 1,
    'contact_id' => null,
    'share_amount' => 100,
]);

// Test contact split
$expense->splits()->create([
    'user_id' => null,
    'contact_id' => 1,
    'share_amount' => 100,
]);

// Test both in same expense
$splits = $expense->splits;
$splits->each(function ($split) {
    echo $split->getMemberName(); // Works for both
});
```
