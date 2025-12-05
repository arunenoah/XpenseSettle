<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Payment;
use App\Models\Comment;
use App\Services\ExpenseService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Helper to create splits with payment records
     */
    private function createSplitsWithPayments($expense, $users, $amount, $markAsPaid = [])
    {
        foreach ($users as $user) {
            $split = ExpenseSplit::create([
                'expense_id' => $expense->id,
                'user_id' => $user->id,
                'share_amount' => $amount
            ]);

            Payment::create([
                'expense_split_id' => $split->id,
                'paid_by' => $user->id,
                'status' => in_array($user->id, $markAsPaid) ? 'paid' : 'pending',
                'paid_date' => in_array($user->id, $markAsPaid) ? now()->subDays(rand(1, 5)) : null,
            ]);
        }
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        if (app()->environment('production')) {
            $this->command->warn('⚠️  Running in production environment');
        }

        // Create initial users if they don't exist
        $this->command->info('👥 Creating/fetching users...');
        $this->call(CreateInitialUsersSeeder::class);

        $arun = User::where('email', 'arun@example.com')->first();
        $velu = User::where('email', 'velu@example.com')->first();
        $dhana = User::where('email', 'dhana@example.com')->first();
        $karthick = User::where('email', 'karthick@example.com')->first();
        $param = User::where('email', 'param@example.com')->first();
        $mohan = User::where('email', 'mohan@example.com')->first();

        if (!$arun || !$velu || !$dhana || !$karthick || !$param || !$mohan) {
            $this->command->error('❌ Not all required users exist. Please create them first.');
            return;
        }

        $this->command->info('✅ All 6 users ready');

        // Create groups
        $this->command->info('👥 Creating groups...');

        // Group 1: Roommates
        $roommates = Group::create([
            'created_by' => $arun->id,
            'name' => 'Apartment 4B - Roommates',
            'description' => 'Shared expenses for our apartment',
            'currency' => 'INR',
        ]);

        GroupMember::create(['group_id' => $roommates->id, 'user_id' => $arun->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $roommates->id, 'user_id' => $velu->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $roommates->id, 'user_id' => $dhana->id, 'role' => 'member']);

        // Group 2: Goa Trip
        $goaTrip = Group::create([
            'created_by' => $karthick->id,
            'name' => 'Goa Weekend Trip',
            'description' => 'Beach vacation expenses - Dec 2024',
            'currency' => 'INR',
            'icon' => '🏖️',
        ]);

        GroupMember::create(['group_id' => $goaTrip->id, 'user_id' => $karthick->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $goaTrip->id, 'user_id' => $arun->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $goaTrip->id, 'user_id' => $velu->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $goaTrip->id, 'user_id' => $param->id, 'role' => 'member']);

        // Group 3: Ooty Trip
        $ootyTrip = Group::create([
            'created_by' => $dhana->id,
            'name' => 'Ooty Hill Station Trip',
            'description' => 'Weekend getaway to the hills',
            'currency' => 'INR',
            'icon' => '⛰️',
        ]);

        GroupMember::create(['group_id' => $ootyTrip->id, 'user_id' => $dhana->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $ootyTrip->id, 'user_id' => $arun->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $ootyTrip->id, 'user_id' => $mohan->id, 'role' => 'member']);

        // Group 3: Office Lunch
        $lunch = Group::create([
            'created_by' => $dhana->id,
            'name' => 'Office Lunch Group',
            'description' => 'Weekly team lunches',
            'currency' => 'INR',
        ]);

        GroupMember::create(['group_id' => $lunch->id, 'user_id' => $dhana->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $lunch->id, 'user_id' => $param->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $lunch->id, 'user_id' => $mohan->id, 'role' => 'member']);

        // Group 5: Hunter Valley Trip
        $hunterValley = Group::create([
            'created_by' => $arun->id,
            'name' => 'Hunter Valley Wine Trip',
            'description' => 'Wine tasting weekend getaway',
            'currency' => 'INR',
            'icon' => '🍷',
        ]);

        GroupMember::create(['group_id' => $hunterValley->id, 'user_id' => $arun->id, 'role' => 'admin']);
        GroupMember::create(['group_id' => $hunterValley->id, 'user_id' => $velu->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $hunterValley->id, 'user_id' => $dhana->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $hunterValley->id, 'user_id' => $karthick->id, 'role' => 'member']);
        GroupMember::create(['group_id' => $hunterValley->id, 'user_id' => $param->id, 'role' => 'member']);

        $this->command->info('✅ Created 5 groups');

        // Create expenses
        $this->command->info('💰 Creating expenses...');

        $expenseService = new ExpenseService();

        // Expense 1: Rent (Equal split)
        $rent = Expense::create([
            'group_id' => $roommates->id,
            'payer_id' => $arun->id,
            'title' => 'Monthly Rent - December',
            'description' => 'Apartment rent for December',
            'amount' => 18000.00,
            'split_type' => 'equal',
            'date' => now()->subDays(5),
            'status' => 'pending',
        ]);

        $rentPerPerson = 18000 / 3;
        $arunRentSplit = ExpenseSplit::create(['expense_id' => $rent->id, 'user_id' => $arun->id, 'share_amount' => $rentPerPerson]);
        $veluRentSplit = ExpenseSplit::create(['expense_id' => $rent->id, 'user_id' => $velu->id, 'share_amount' => $rentPerPerson]);
        $dhanaRentSplit = ExpenseSplit::create(['expense_id' => $rent->id, 'user_id' => $dhana->id, 'share_amount' => $rentPerPerson]);

        // Create payment records for all splits
        Payment::create(['expense_split_id' => $arunRentSplit->id, 'paid_by' => $arun->id, 'status' => 'pending']);
        Payment::create(['expense_split_id' => $veluRentSplit->id, 'paid_by' => $velu->id, 'status' => 'paid', 'paid_date' => now()->subDays(3), 'notes' => 'Paid via UPI']);
        Payment::create(['expense_split_id' => $dhanaRentSplit->id, 'paid_by' => $dhana->id, 'status' => 'pending']);

        // Expense 2: Groceries (Custom split)
        $groceries = Expense::create([
            'group_id' => $roommates->id,
            'payer_id' => $velu->id,
            'title' => 'Weekly Groceries',
            'description' => 'Big Bazaar shopping',
            'amount' => 2450.00,
            'split_type' => 'custom',
            'date' => now()->subDays(3),
            'status' => 'pending',
        ]);

        $arunGrocSplit = ExpenseSplit::create(['expense_id' => $groceries->id, 'user_id' => $arun->id, 'share_amount' => 850.00]);
        $veluGrocSplit = ExpenseSplit::create(['expense_id' => $groceries->id, 'user_id' => $velu->id, 'share_amount' => 900.00]);
        $dhanaGrocSplit = ExpenseSplit::create(['expense_id' => $groceries->id, 'user_id' => $dhana->id, 'share_amount' => 700.00]);

        // Create pending payment records
        Payment::create(['expense_split_id' => $arunGrocSplit->id, 'paid_by' => $arun->id, 'status' => 'pending']);
        Payment::create(['expense_split_id' => $veluGrocSplit->id, 'paid_by' => $velu->id, 'status' => 'pending']);
        Payment::create(['expense_split_id' => $dhanaGrocSplit->id, 'paid_by' => $dhana->id, 'status' => 'pending']);

        // Expense 3: Utilities (Percentage split)
        $utilities = Expense::create([
            'group_id' => $roommates->id,
            'payer_id' => $dhana->id,
            'title' => 'Electricity & Water Bill',
            'description' => 'November utilities',
            'amount' => 1800.00,
            'split_type' => 'percentage',
            'date' => now()->subDays(7),
            'status' => 'pending',
        ]);

        ExpenseSplit::create(['expense_id' => $utilities->id, 'user_id' => $arun->id, 'share_amount' => 720.00, 'percentage' => 40]);
        ExpenseSplit::create(['expense_id' => $utilities->id, 'user_id' => $velu->id, 'share_amount' => 540.00, 'percentage' => 30]);
        ExpenseSplit::create(['expense_id' => $utilities->id, 'user_id' => $dhana->id, 'share_amount' => 540.00, 'percentage' => 30]);

        // All paid for utilities
        foreach ($utilities->splits as $split) {
            Payment::create([
                'expense_split_id' => $split->id,
                'paid_by' => $split->user_id,
                'status' => 'paid',
                'paid_date' => now()->subDays(5),
            ]);
        }
        $utilities->update(['status' => 'fully_paid']);

        // === GOA TRIP EXPENSES ===
        // Expense 4: Hotel booking
        $goaHotel = Expense::create([
            'group_id' => $goaTrip->id,
            'payer_id' => $karthick->id,
            'title' => 'Goa Beach Resort',
            'description' => '3 nights at Taj Fort Aguada',
            'amount' => 24000.00,
            'split_type' => 'equal',
            'date' => now()->subDays(10),
            'status' => 'pending',
        ]);

        $hotelPerPerson = 24000 / 4;
        foreach ([$karthick, $arun, $velu, $param] as $user) {
            ExpenseSplit::create(['expense_id' => $goaHotel->id, 'user_id' => $user->id, 'share_amount' => $hotelPerPerson]);
        }

        // Mark Dhana and Karthick as paid
        $hotelSplits = $goaHotel->splits()->whereIn('user_id', [$karthick->id, $param->id])->get();
        foreach ($hotelSplits as $split) {
            Payment::create([
                'expense_split_id' => $split->id,
                'paid_by' => $split->user_id,
                'status' => 'paid',
                'paid_date' => now()->subDays(8),
                'notes' => 'Paid via Google Pay',
            ]);
        }

        // Expense 5: Seafood Dinner
        $goaDinner = Expense::create([
            'group_id' => $goaTrip->id,
            'payer_id' => $param->id,
            'title' => 'Seafood Dinner',
            'description' => 'Fisherman\'s Wharf Restaurant',
            'amount' => 3600.00,
            'split_type' => 'equal',
            'date' => now()->subDays(8),
            'status' => 'fully_paid',
        ]);

        $dinnerPerPerson = 3600 / 4;
        foreach ([$karthick, $arun, $velu, $param] as $user) {
            $split = ExpenseSplit::create([
                'expense_id' => $goaDinner->id,
                'user_id' => $user->id,
                'share_amount' => $dinnerPerPerson
            ]);

            Payment::create([
                'expense_split_id' => $split->id,
                'paid_by' => $user->id,
                'status' => 'paid',
                'paid_date' => now()->subDays(7),
            ]);
        }

        // Expense 6: Groceries for beach house
        $goaGroceries = Expense::create([
            'group_id' => $goaTrip->id,
            'payer_id' => $velu->id,
            'title' => 'Groceries - Costco',
            'description' => 'Snacks, drinks, breakfast items',
            'amount' => 2800.00,
            'split_type' => 'equal',
            'date' => now()->subDays(9),
            'status' => 'pending',
        ]);

        foreach ([$karthick, $arun, $velu, $param] as $user) {
            ExpenseSplit::create(['expense_id' => $goaGroceries->id, 'user_id' => $user->id, 'share_amount' => 700]);
        }

        // Expense 7: Outside food - Lunch
        $goaLunch = Expense::create([
            'group_id' => $goaTrip->id,
            'payer_id' => $arun->id,
            'title' => 'Lunch at Baga Beach',
            'description' => 'Britto\'s Beach Shack',
            'amount' => 1800.00,
            'split_type' => 'equal',
            'date' => now()->subDays(7),
            'status' => 'pending',
        ]);

        foreach ([$karthick, $arun, $velu, $param] as $user) {
            ExpenseSplit::create(['expense_id' => $goaLunch->id, 'user_id' => $user->id, 'share_amount' => 450]);
        }

        // === OOTY TRIP EXPENSES ===
        // Expense 8: Hotel
        $ootyHotel = Expense::create([
            'group_id' => $ootyTrip->id,
            'payer_id' => $dhana->id,
            'title' => 'Hotel Savoy',
            'description' => '2 nights stay with breakfast',
            'amount' => 12000.00,
            'split_type' => 'equal',
            'date' => now()->subDays(15),
            'status' => 'pending',
        ]);

        foreach ([$dhana, $arun, $mohan] as $user) {
            ExpenseSplit::create(['expense_id' => $ootyHotel->id, 'user_id' => $user->id, 'share_amount' => 4000]);
        }

        // Mark Arun as paid
        $ootyHotelSplit = $ootyHotel->splits()->where('user_id', $arun->id)->first();
        Payment::create([
            'expense_split_id' => $ootyHotelSplit->id,
            'paid_by' => $arun->id,
            'status' => 'paid',
            'paid_date' => now()->subDays(14),
            'notes' => 'Paid via PhonePe',
        ]);

        // Expense 9: Food expenses
        $ootyFood = Expense::create([
            'group_id' => $ootyTrip->id,
            'payer_id' => $mohan->id,
            'title' => 'Restaurant Bills',
            'description' => 'Earl\'s Secret + Nahar Sidewalk Cafe',
            'amount' => 2400.00,
            'split_type' => 'equal',
            'date' => now()->subDays(14),
            'status' => 'fully_paid',
        ]);

        foreach ([$dhana, $arun, $mohan] as $user) {
            $split = ExpenseSplit::create([
                'expense_id' => $ootyFood->id,
                'user_id' => $user->id,
                'share_amount' => 800
            ]);

            Payment::create([
                'expense_split_id' => $split->id,
                'paid_by' => $user->id,
                'status' => 'paid',
                'paid_date' => now()->subDays(13),
            ]);
        }

        // Expense 10: Groceries
        $ootyGroceries = Expense::create([
            'group_id' => $ootyTrip->id,
            'payer_id' => $arun->id,
            'title' => 'Groceries - Nilgiris Store',
            'description' => 'Tea, snacks, and essentials',
            'amount' => 1200.00,
            'split_type' => 'equal',
            'date' => now()->subDays(15),
            'status' => 'pending',
        ]);

        foreach ([$dhana, $arun, $mohan] as $user) {
            ExpenseSplit::create(['expense_id' => $ootyGroceries->id, 'user_id' => $user->id, 'share_amount' => 400]);
        }

        // Create expenses for Lunch group
        // Expense 6: Team lunch
        $teamLunch = Expense::create([
            'group_id' => $lunch->id,
            'payer_id' => $dhana->id,
            'title' => 'Friday Team Lunch',
            'description' => 'Saravana Bhavan',
            'amount' => 950.00,
            'split_type' => 'equal',
            'date' => now()->subDays(2),
            'status' => 'pending',
        ]);

        $lunchPerPerson = 950 / 3;
        ExpenseSplit::create(['expense_id' => $teamLunch->id, 'user_id' => $dhana->id, 'share_amount' => $lunchPerPerson]);
        ExpenseSplit::create(['expense_id' => $teamLunch->id, 'user_id' => $param->id, 'share_amount' => $lunchPerPerson]);
        ExpenseSplit::create(['expense_id' => $teamLunch->id, 'user_id' => $mohan->id, 'share_amount' => $lunchPerPerson]);

        // === HUNTER VALLEY TRIP EXPENSES ===
        // Expense 11: Costco Bill
        $costcoBill = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $velu->id,
            'title' => 'Costco Shopping Bill',
            'description' => 'Bulk groceries, snacks, and drinks for the trip',
            'amount' => 8500.00,
            'split_type' => 'equal',
            'date' => now()->subDays(4),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($costcoBill, [$arun, $velu, $dhana, $karthick, $param], 1700);

        // Expense 12: Meat & Fish Purchase
        $meatFish = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $karthick->id,
            'title' => 'Meat & Fish Purchase',
            'description' => 'BBQ supplies - Steaks, Salmon, Prawns from butcher',
            'amount' => 6200.00,
            'split_type' => 'equal',
            'date' => now()->subDays(3),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($meatFish, [$arun, $velu, $dhana, $karthick, $param], 1240);

        // Expense 13: Inner Peace Spa
        $spa = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $dhana->id,
            'title' => 'Inner Peace Spa & Wellness',
            'description' => 'Group spa session and massage therapy',
            'amount' => 15000.00,
            'split_type' => 'equal',
            'date' => now()->subDays(2),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($spa, [$arun, $velu, $dhana, $karthick, $param], 3000);

        // Expense 14: Coffee Expenses
        $coffee = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $param->id,
            'title' => 'Coffee Shop Expenses',
            'description' => 'Starbucks + Local cafe visits',
            'amount' => 1800.00,
            'split_type' => 'equal',
            'date' => now()->subDays(1),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($coffee, [$arun, $velu, $dhana, $karthick, $param], 360);

        // Expense 15: Wine Tasting Tour
        $wineTour = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $arun->id,
            'title' => 'Wine Tasting Tour',
            'description' => 'Full day wine tour with 5 wineries',
            'amount' => 12500.00,
            'split_type' => 'equal',
            'date' => now()->subDays(2),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($wineTour, [$arun, $velu, $dhana, $karthick, $param], 2500);

        // Expense 16: Accommodation
        $hvAccommodation = Expense::create([
            'group_id' => $hunterValley->id,
            'payer_id' => $arun->id,
            'title' => 'Hunter Valley Resort',
            'description' => '3 nights luxury villa with pool',
            'amount' => 35000.00,
            'split_type' => 'equal',
            'date' => now()->subDays(5),
            'status' => 'pending',
        ]);
        $this->createSplitsWithPayments($hvAccommodation, [$arun, $velu, $dhana, $karthick, $param], 7000);

        $this->command->info('✅ Created 19 expenses');

        // Add comments
        $this->command->info('💬 Adding comments...');

        Comment::create([
            'expense_id' => $rent->id,
            'user_id' => $velu->id,
            'content' => 'Just paid my share via UPI. Reference: RENT-DEC-2024',
        ]);

        Comment::create([
            'expense_id' => $groceries->id,
            'user_id' => $dhana->id,
            'content' => 'Thanks for getting the groceries! Will pay you tomorrow.',
        ]);

        Comment::create([
            'expense_id' => $goaHotel->id,
            'user_id' => $karthick->id,
            'content' => 'Hotel confirmation number: GOA12345. Check-in is 2 PM on Friday.',
        ]);

        Comment::create([
            'expense_id' => $goaHotel->id,
            'user_id' => $param->id,
            'content' => 'Just paid! Can\'t wait for the trip! 🏖️',
        ]);

        Comment::create([
            'expense_id' => $goaDinner->id,
            'user_id' => $arun->id,
            'content' => 'That was an amazing dinner! Thanks Param for covering it initially.',
        ]);

        Comment::create([
            'expense_id' => $goaGroceries->id,
            'user_id' => $velu->id,
            'content' => 'Got everything from Costco. Will need payment from everyone.',
        ]);

        Comment::create([
            'expense_id' => $ootyHotel->id,
            'user_id' => $dhana->id,
            'content' => 'Booked the best hotel in Ooty! Amazing views 🏔️',
        ]);

        Comment::create([
            'expense_id' => $costcoBill->id,
            'user_id' => $velu->id,
            'content' => 'Got everything we need from Costco! Huge savings on bulk items 🛒',
        ]);

        Comment::create([
            'expense_id' => $meatFish->id,
            'user_id' => $karthick->id,
            'content' => 'Premium cuts from the butcher! BBQ is going to be epic 🥩🐟',
        ]);

        Comment::create([
            'expense_id' => $spa->id,
            'user_id' => $dhana->id,
            'content' => 'This spa is incredible! Best relaxation ever 🧘‍♀️✨',
        ]);

        Comment::create([
            'expense_id' => $coffee->id,
            'user_id' => $param->id,
            'content' => 'Coffee runs keeping us energized! ☕💪',
        ]);

        Comment::create([
            'expense_id' => $wineTour->id,
            'user_id' => $arun->id,
            'content' => 'Amazing wine tour! Visited 5 wineries, tasted 20+ wines 🍷',
        ]);

        Comment::create([
            'expense_id' => $hvAccommodation->id,
            'user_id' => $arun->id,
            'content' => 'Luxury villa is stunning! Pool, views, everything perfect 🏊‍♂️',
        ]);

        $this->command->info('✅ Added 13 comments');

        // Summary
        $this->command->newLine();
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('📊 Summary:');
        $this->command->info('  - Users: 6 (already existed)');
        $this->command->info('  - Groups: 5 (Roommates, Goa, Ooty, Office Lunch, Hunter Valley)');
        $this->command->info('  - Expenses: 19');
        $this->command->info('  - Payments: Multiple marked as paid');
        $this->command->info('  - Comments: 13');
        $this->command->newLine();
        $this->command->info('✅ Sample data created for all groups!');
        $this->command->newLine();
    }
}
