<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SetUserPinsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users without a PIN
        $users = User::whereNull('pin')->orWhere('pin', '')->get();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found without PINs.');
            return;
        }
        
        $this->command->info("Found {$users->count()} users without PINs.");
        $this->command->newLine();
        
        $usedPins = [];
        
        foreach ($users as $user) {
            // Generate a unique random 6-digit PIN
            do {
                $pin = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            } while (in_array($pin, $usedPins) || User::where('pin', $pin)->exists());
            
            $usedPins[] = $pin;
            
            // Update user with the PIN
            $user->pin = $pin;
            $user->save();
            
            $this->command->info("User: {$user->name} ({$user->email}) - PIN: {$pin}");
        }
        
        $this->command->newLine();
        $this->command->info('✅ All users have been assigned unique PINs!');
        $this->command->warn('⚠️  Please save these PINs and share them with the respective users.');
    }
}
