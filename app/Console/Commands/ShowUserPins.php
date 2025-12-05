<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ShowUserPins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:show-pins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display all users with their PINs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all(['id', 'name', 'email', 'pin']);

        if ($users->isEmpty()) {
            $this->warn('No users found in the database.');
            return 0;
        }

        $this->info('Current Users and their PINs:');
        $this->newLine();

        $tableData = [];
        foreach ($users as $user) {
            $tableData[] = [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'PIN' => $user->pin ?? 'Not Set',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Email', 'PIN'],
            $tableData
        );

        $this->newLine();
        $this->info("Total Users: {$users->count()}");

        return 0;
    }
}
