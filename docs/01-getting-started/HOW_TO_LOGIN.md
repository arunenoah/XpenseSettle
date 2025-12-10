# 🔐 How to Access the Application

## Quick Start

1. **Open your browser** and go to: http://localhost:8001

2. **Click "Sign In"** button on the homepage

3. **Login with any test account:**

   ```
   Email: arun@example.com
   Password: password
   ```

   OR use any of these accounts:
   - velu@example.com
   - dhana@example.com
   - karthick@example.com
   - param@example.com
   - mohan@example.com
   
   (All passwords are: `password`)

4. **You'll be redirected to the Dashboard** where you can see:
   - Your groups
   - Pending payments
   - Recent expenses
   - Statistics

## Test Accounts

| Email | Name | Role | Groups |
|-------|------|------|--------|
| arun@example.com | Arun Kumar | Admin | Apartment 4B - Roommates |
| velu@example.com | Velu | Member | Roommates, Goa Trip |
| dhana@example.com | Dhana | Admin | Office Lunch, Roommates |
| karthick@example.com | Karthick | Admin | Goa Weekend Trip |
| param@example.com | Param | Member | Goa Trip, Office Lunch |
| mohan@example.com | Mohan | Member | Office Lunch |

## What You Can Do

### As Arun (arun@example.com):
- View "Apartment 4B - Roommates" group (Admin)
- See ₹18,000 rent expense (you paid, Velu paid his share)
- See ₹2,450 groceries expense
- See ₹1,800 utilities (fully paid)
- Mark your pending payments
- Add new expenses
- Add members to group

### As Karthick (karthick@example.com):
- View "Goa Weekend Trip" group (Admin)
- See ₹24,000 hotel booking (partially paid)
- See ₹3,600 seafood dinner (fully paid)
- Manage trip expenses

### As Dhana (dhana@example.com):
- Admin of "Office Lunch Group"
- Member of "Apartment 4B - Roommates"
- See multiple groups

## Navigation

After login, you can:
- **Dashboard** - Overview of all your expenses
- **Groups** - View and manage your groups
- **Expenses** - View expense details
- **Payments** - Mark payments as paid
- **Comments** - Add notes to expenses
- **Export** - Download CSV reports

## Troubleshooting

**Error: "Call to undefined method middleware()"**
- This means you're trying to access `/dashboard` directly without logging in
- Solution: Go to http://localhost:8001 first, then click "Sign In"

**Can't see the login page?**
- Make sure the server is running: `php artisan serve`
- Check the URL: http://localhost:8001 (not 8000)

**Forgot which port?**
- Check terminal output when you ran `php artisan serve`
- It will show: "Server running on [http://127.0.0.1:8001]"

## Quick Test Flow

1. Login as `arun@example.com`
2. Click on "Apartment 4B - Roommates"
3. View the rent expense
4. See that Velu already paid
5. Mark your share as paid
6. Add a comment
7. Export group data to CSV

Enjoy testing! 🎉
