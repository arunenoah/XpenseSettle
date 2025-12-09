<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlanTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test users with different plans
        $freeUser = User::firstOrCreate(
            ['email' => 'free@test.com'],
            [
                'name' => 'Free User',
                'password' => Hash::make('password'),
                'pin' => '111111',
                'plan' => 'free',
            ]
        );

        $tripPassUser = User::firstOrCreate(
            ['email' => 'trippass@test.com'],
            [
                'name' => 'Trip Pass User',
                'password' => Hash::make('password'),
                'pin' => '222222',
                'plan' => 'free', // User plan is free, but group will have trip pass
            ]
        );

        $lifetimeUser = User::firstOrCreate(
            ['email' => 'lifetime@test.com'],
            [
                'name' => 'Lifetime User',
                'password' => Hash::make('password'),
                'pin' => '333333',
                'plan' => 'lifetime',
            ]
        );

        $this->command->info('✓ Created 3 test users:');
        $this->command->info('  - free@test.com (Free Plan) - PIN: 111111');
        $this->command->info('  - trippass@test.com (Trip Pass - via group) - PIN: 222222');
        $this->command->info('  - lifetime@test.com (Lifetime Plan) - PIN: 333333');
        $this->command->info('  Password for all: password');

        // Create test groups
        $freeGroup = Group::firstOrCreate(
            ['name' => 'Free Plan Test Group'],
            [
                'created_by' => $freeUser->id,
                'description' => 'Testing free plan limits (5 OCR scans)',
                'currency' => 'AUD',
                'plan' => 'free',
                'ocr_scans_used' => 0,
            ]
        );

        $tripPassGroup = Group::firstOrCreate(
            ['name' => 'Trip Pass Test Group'],
            [
                'created_by' => $tripPassUser->id,
                'description' => 'Testing trip pass (unlimited OCR)',
                'currency' => 'AUD',
                'plan' => 'trip_pass',
                'plan_expires_at' => now()->addYear(),
                'ocr_scans_used' => 0,
            ]
        );

        $lifetimeGroup = Group::firstOrCreate(
            ['name' => 'Lifetime Test Group'],
            [
                'created_by' => $lifetimeUser->id,
                'description' => 'Testing lifetime plan (unlimited everything)',
                'currency' => 'AUD',
                'plan' => 'free', // Group plan doesn't matter, user has lifetime
                'ocr_scans_used' => 0,
            ]
        );

        $nearLimitGroup = Group::firstOrCreate(
            ['name' => 'Near Limit Test Group'],
            [
                'created_by' => $freeUser->id,
                'description' => 'Testing near OCR limit (4/5 scans used)',
                'currency' => 'AUD',
                'plan' => 'free',
                'ocr_scans_used' => 4, // 1 scan remaining
            ]
        );

        $atLimitGroup = Group::firstOrCreate(
            ['name' => 'At Limit Test Group'],
            [
                'created_by' => $freeUser->id,
                'description' => 'Testing at OCR limit (5/5 scans used)',
                'currency' => 'AUD',
                'plan' => 'free',
                'ocr_scans_used' => 5, // No scans remaining
            ]
        );

        $this->command->info('');
        $this->command->info('✓ Created 5 test groups:');
        $this->command->info('  1. Free Plan Test Group (0/5 OCR scans used)');
        $this->command->info('  2. Trip Pass Test Group (unlimited OCR)');
        $this->command->info('  3. Lifetime Test Group (unlimited everything)');
        $this->command->info('  4. Near Limit Test Group (4/5 OCR scans used)');
        $this->command->info('  5. At Limit Test Group (5/5 OCR scans used - should show upgrade prompt)');

        // Add members to groups
        foreach ([$freeGroup, $nearLimitGroup, $atLimitGroup] as $group) {
            GroupMember::firstOrCreate([
                'group_id' => $group->id,
                'user_id' => $freeUser->id,
            ], [
                'role' => 'admin',
                'family_count' => 1,
            ]);
        }

        GroupMember::firstOrCreate([
            'group_id' => $tripPassGroup->id,
            'user_id' => $tripPassUser->id,
        ], [
            'role' => 'admin',
            'family_count' => 1,
        ]);

        GroupMember::firstOrCreate([
            'group_id' => $lifetimeGroup->id,
            'user_id' => $lifetimeUser->id,
        ], [
            'role' => 'admin',
            'family_count' => 1,
        ]);

        $this->command->info('');
        $this->command->info('✓ Added users as group members');
        $this->command->info('');
        $this->command->info('🧪 TEST SCENARIOS:');
        $this->command->info('');
        $this->command->info('1. Login as free@test.com:');
        $this->command->info('   - "Free Plan Test Group" → Should allow 5 OCR scans');
        $this->command->info('   - "Near Limit Test Group" → Should show "1 scan remaining"');
        $this->command->info('   - "At Limit Test Group" → Should show upgrade prompt 🔒');
        $this->command->info('');
        $this->command->info('2. Login as trippass@test.com:');
        $this->command->info('   - "Trip Pass Test Group" → Should show "Unlimited OCR scans" ✓');
        $this->command->info('');
        $this->command->info('3. Login as lifetime@test.com:');
        $this->command->info('   - "Lifetime Test Group" → Should show "Unlimited OCR scans" ✓');
        $this->command->info('');
        $this->command->info('📝 To test:');
        $this->command->info('   1. Login with any test account');
        $this->command->info('   2. Go to the group');
        $this->command->info('   3. Click "Add Expense"');
        $this->command->info('   4. Upload a receipt image');
        $this->command->info('   5. Check the plan badge and OCR section');
    }
}
