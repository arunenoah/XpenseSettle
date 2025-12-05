<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateInitialUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Arun', 'email' => 'arun@example.com'],
            ['name' => 'Velu', 'email' => 'velu@example.com'],
            ['name' => 'Dhana', 'email' => 'dhana@example.com'],
            ['name' => 'Karthick', 'email' => 'karthick@example.com'],
            ['name' => 'Param', 'email' => 'param@example.com'],
            ['name' => 'Mohan', 'email' => 'mohan@example.com'],
        ];

        $this->command->info('Creating users with unique PINs...');
        $this->command->newLine();

        $usedPins = [];
        $createdUsers = [];

        foreach ($users as $userData) {
            // Check if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                $this->command->warn("User {$userData['email']} already exists. Skipping...");
                continue;
            }

            // Generate a unique random 6-digit PIN
            do {
                $pin = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            } while (in_array($pin, $usedPins) || User::where('pin', $pin)->exists());

            $usedPins[] = $pin;

            // Create user with default password
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'), // Default password
                'pin' => $pin,
            ]);

            $createdUsers[] = [
                'Name' => $user->name,
                'Email' => $user->email,
                'PIN' => $pin,
            ];

            $this->command->info("✓ Created: {$user->name} ({$user->email}) - PIN: {$pin}");
        }

        if (!empty($createdUsers)) {
            $this->command->newLine();
            $this->command->table(
                ['Name', 'Email', 'PIN'],
                $createdUsers
            );

            $this->command->newLine();
            $this->command->info('✅ All users created successfully!');
            $this->command->warn('⚠️  Default password for all users: password123');
            $this->command->warn('⚠️  Users can login using their 6-digit PIN only.');
        } else {
            $this->command->warn('No new users were created.');
        }
    }
}
