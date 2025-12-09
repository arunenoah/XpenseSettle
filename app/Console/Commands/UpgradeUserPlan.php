<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PlanService;
use Illuminate\Console\Command;

class UpgradeUserPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:upgrade-plan {email} {plan=lifetime}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade a user to Lifetime or manage their plan';

    private PlanService $planService;

    public function __construct(PlanService $planService)
    {
        parent::__construct();
        $this->planService = $planService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $plan = $this->argument('plan');

        // Find user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }

        // Validate plan
        if (!in_array($plan, ['free', 'lifetime'])) {
            $this->error("Invalid plan '{$plan}'. Must be 'free' or 'lifetime'");
            return 1;
        }

        // Show current status
        $this->info("Current Status:");
        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Current Plan: {$user->plan}");

        // Confirm
        if (!$this->confirm("Upgrade {$user->name} to {$plan} plan?", true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Upgrade
        if ($plan === 'lifetime') {
            $this->planService->activateLifetimePlan($user);
            $this->info("✅ {$user->name} has been upgraded to Lifetime plan!");
            $this->line("   - Unlimited OCR scans across all groups");
            $this->line("   - Unlimited attachments");
            $this->line("   - PDF/Excel export enabled");
            $this->line("   - All premium features unlocked");
        } else {
            $user->update(['plan' => 'free', 'plan_expires_at' => null]);
            $this->info("✅ {$user->name} has been set to Free plan");
        }

        return 0;
    }
}
