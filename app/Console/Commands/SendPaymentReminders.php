<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-payment-reminders
                            {--days-ago=7 : Send reminders for expenses older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminder notifications for unpaid expenses';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(NotificationService $notificationService)
    {
        $daysAgo = (int) $this->option('days-ago');
        $cutoffDate = now()->subDays($daysAgo);

        $this->info("Sending payment reminders for expenses created before {$cutoffDate->format('Y-m-d')}...");

        // Find all unpaid expense splits for expenses created before the cutoff date
        $unpaidSplits = ExpenseSplit::whereHas('expense', function ($query) use ($cutoffDate) {
                $query->where('created_at', '<', $cutoffDate);
            })
            ->whereDoesntHave('payment', function ($query) {
                $query->where('status', 'paid');
            })
            ->with('user', 'expense')
            ->get();

        if ($unpaidSplits->isEmpty()) {
            $this->info('No unpaid expenses found.');
            return 0;
        }

        $count = 0;

        // Send reminder notification to each user with unpaid splits
        foreach ($unpaidSplits as $split) {
            try {
                $notificationService->sendPaymentReminder($split->user, $split->expense);
                $count++;

                $this->line("✓ Reminder sent to {$split->user->name} for expense: {$split->expense->title}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to {$split->user->name}: {$e->getMessage()}");
            }
        }

        $this->info("\nSuccessfully sent {$count} payment reminders.");
        return 0;
    }
}
