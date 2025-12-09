<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class HashExistingPins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pins:hash-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hash existing plain text PINs in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PIN migration...');
        
        // Get all users
        $users = User::all();
        $updated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Check if PIN is already hashed (bcrypt hashes start with $2y$)
            if ($user->pin && !str_starts_with($user->pin, '$2y$')) {
                $plainPin = $user->pin;
                
                // Update using DB query to bypass model casting
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['pin' => Hash::make($plainPin)]);
                
                $this->info("✓ Hashed PIN for user: {$user->email}");
                $updated++;
            } else {
                $skipped++;
            }

            // Check admin_pin
            if ($user->admin_pin && !str_starts_with($user->admin_pin, '$2y$')) {
                $plainAdminPin = $user->admin_pin;
                
                // Update using DB query to bypass model casting
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['admin_pin' => Hash::make($plainAdminPin)]);
                
                $this->info("✓ Hashed admin PIN for user: {$user->email}");
                $updated++;
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Migration complete!");
        $this->info("Updated: {$updated} PINs");
        $this->info("Skipped: {$skipped} (already hashed)");
        $this->info(str_repeat('=', 50));

        return 0;
    }
}
