<?php

namespace App\Services;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Get user achievements and stats.
     */
    public function getUserAchievements(User $user): array
    {
        $stats = $this->getUserStats($user);
        $achievements = [];

        // Payment-related achievements
        if ($stats['total_payments_made'] >= 1) {
            $achievements[] = [
                'name' => 'First Payment',
                'description' => 'Made your first payment',
                'icon' => '💰',
                'unlocked' => true,
            ];
        }

        if ($stats['total_payments_made'] >= 10) {
            $achievements[] = [
                'name' => 'Regular Payer',
                'description' => 'Made 10 payments',
                'icon' => '💳',
                'unlocked' => true,
            ];
        }

        if ($stats['total_payments_made'] >= 50) {
            $achievements[] = [
                'name' => 'Payment Master',
                'description' => 'Made 50 payments',
                'icon' => '🏆',
                'unlocked' => true,
            ];
        }

        // Debt clearing achievements
        if ($stats['debts_cleared'] >= 5) {
            $achievements[] = [
                'name' => 'Debt Clearer',
                'description' => 'Cleared 5 debts',
                'icon' => '✅',
                'unlocked' => true,
            ];
        }

        if ($stats['debts_cleared'] >= 20) {
            $achievements[] = [
                'name' => 'Debt Free Champion',
                'description' => 'Cleared 20 debts',
                'icon' => '🎯',
                'unlocked' => true,
            ];
        }

        // On-time payment achievements
        $onTimePercentage = $stats['total_payments_made'] > 0
            ? ($stats['on_time_payments'] / $stats['total_payments_made']) * 100
            : 0;

        if ($onTimePercentage >= 90 && $stats['total_payments_made'] >= 10) {
            $achievements[] = [
                'name' => 'Punctual Payer',
                'description' => '90% on-time payment rate',
                'icon' => '⏰',
                'unlocked' => true,
            ];
        }

        // Group participation achievements
        if ($stats['groups_joined'] >= 3) {
            $achievements[] = [
                'name' => 'Social Butterfly',
                'description' => 'Member of 3+ groups',
                'icon' => '🦋',
                'unlocked' => true,
            ];
        }

        if ($stats['groups_created'] >= 1) {
            $achievements[] = [
                'name' => 'Group Founder',
                'description' => 'Created your first group',
                'icon' => '👥',
                'unlocked' => true,
            ];
        }

        // Expense tracking achievements
        if ($stats['expenses_created'] >= 10) {
            $achievements[] = [
                'name' => 'Expense Tracker',
                'description' => 'Created 10 expenses',
                'icon' => '📊',
                'unlocked' => true,
            ];
        }

        // Trust score achievement
        if ($stats['trust_score'] >= 90) {
            $achievements[] = [
                'name' => 'Trusted Member',
                'description' => 'Trust score above 90%',
                'icon' => '⭐',
                'unlocked' => true,
            ];
        }

        return [
            'achievements' => $achievements,
            'stats' => $stats,
            'level' => $this->calculateUserLevel($stats),
            'next_level_progress' => $this->getNextLevelProgress($stats),
        ];
    }

    /**
     * Get comprehensive user statistics.
     */
    public function getUserStats(User $user): array
    {
        // Payment statistics
        $totalPaymentsMade = $user->payments()
            ->where('status', 'paid')
            ->count();

        $onTimePayments = $user->payments()
            ->where('status', 'paid')
            ->whereRaw('paid_date <= DATE_ADD(created_at, INTERVAL 7 DAY)')
            ->count();

        $debtsCleared = $user->expenseSplits()
            ->whereHas('payment', function ($q) {
                $q->where('status', 'paid');
            })
            ->count();

        // Group statistics
        $groupsJoined = $user->groups()->count();
        $groupsCreated = $user->createdGroups()->count();

        // Expense statistics
        $expensesCreated = $user->paidExpenses()->count();
        $totalAmountPaid = $user->paidExpenses()->sum('amount');
        $totalAmountOwed = $user->expenseSplits()->sum('share_amount');

        // Current balance
        $currentBalance = $this->calculateCurrentBalance($user);

        // Trust score (based on payment behavior)
        $trustScore = $this->calculateTrustScore($user);

        // Streak (consecutive days with activity)
        $currentStreak = $this->calculateStreak($user);

        return [
            'total_payments_made' => $totalPaymentsMade,
            'on_time_payments' => $onTimePayments,
            'debts_cleared' => $debtsCleared,
            'groups_joined' => $groupsJoined,
            'groups_created' => $groupsCreated,
            'expenses_created' => $expensesCreated,
            'total_amount_paid' => $totalAmountPaid,
            'total_amount_owed' => $totalAmountOwed,
            'current_balance' => $currentBalance,
            'trust_score' => $trustScore,
            'current_streak' => $currentStreak,
        ];
    }

    /**
     * Calculate user's current balance across all groups.
     */
    private function calculateCurrentBalance(User $user): float
    {
        $owed = 0;
        $owes = 0;

        foreach ($user->groups as $group) {
            foreach ($group->expenses as $expense) {
                if ($expense->payer_id === $user->id) {
                    // User paid, calculate what others owe
                    $owed += $expense->splits()
                        ->where('user_id', '!=', $user->id)
                        ->whereDoesntHave('payment', function ($q) {
                            $q->where('status', 'paid');
                        })
                        ->sum('share_amount');
                } else {
                    // User owes
                    $split = $expense->splits()->where('user_id', $user->id)->first();
                    if ($split && (!$split->payment || $split->payment->status !== 'paid')) {
                        $owes += $split->share_amount;
                    }
                }
            }
        }

        return round($owed - $owes, 2);
    }

    /**
     * Calculate trust score based on payment behavior.
     */
    private function calculateTrustScore(User $user): float
    {
        $totalSplits = $user->expenseSplits()->count();

        if ($totalSplits === 0) {
            return 100;
        }

        $paidSplits = $user->expenseSplits()
            ->whereHas('payment', function ($q) {
                $q->where('status', 'paid');
            })
            ->count();

        $onTimeSplits = $user->expenseSplits()
            ->whereHas('payment', function ($q) {
                $q->where('status', 'paid')
                    ->whereRaw('paid_date <= DATE_ADD(created_at, INTERVAL 7 DAY)');
            })
            ->count();

        // Calculate score: 50% for payment completion, 50% for on-time payments
        $completionScore = ($paidSplits / $totalSplits) * 50;
        $timelinessScore = $paidSplits > 0 ? ($onTimeSplits / $paidSplits) * 50 : 0;

        return round($completionScore + $timelinessScore, 2);
    }

    /**
     * Calculate user's activity streak.
     */
    private function calculateStreak(User $user): int
    {
        $activities = DB::table('expenses')
            ->where('payer_id', $user->id)
            ->select(DB::raw('DATE(created_at) as activity_date'))
            ->union(
                DB::table('payments')
                    ->where('paid_by', $user->id)
                    ->select(DB::raw('DATE(created_at) as activity_date'))
            )
            ->orderBy('activity_date', 'desc')
            ->get();

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($activities as $activity) {
            $activityDate = \Carbon\Carbon::parse($activity->activity_date)->startOfDay();

            if ($activityDate->eq($currentDate) || $activityDate->eq($currentDate->copy()->subDay())) {
                $streak++;
                $currentDate = $activityDate->copy()->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calculate user level based on stats.
     */
    private function calculateUserLevel(array $stats): int
    {
        $points = 0;

        // Points from payments
        $points += $stats['total_payments_made'] * 10;
        $points += $stats['on_time_payments'] * 5;
        $points += $stats['debts_cleared'] * 15;

        // Points from groups
        $points += $stats['groups_joined'] * 20;
        $points += $stats['groups_created'] * 50;

        // Points from expenses
        $points += $stats['expenses_created'] * 8;

        // Points from trust score
        $points += $stats['trust_score'];

        // Points from streak
        $points += $stats['current_streak'] * 10;

        // Calculate level (every 500 points = 1 level)
        return floor($points / 500) + 1;
    }

    /**
     * Get progress to next level.
     */
    private function getNextLevelProgress(array $stats): array
    {
        $currentLevel = $this->calculateUserLevel($stats);
        $pointsForCurrentLevel = ($currentLevel - 1) * 500;

        // Recalculate total points
        $totalPoints = 0;
        $totalPoints += $stats['total_payments_made'] * 10;
        $totalPoints += $stats['on_time_payments'] * 5;
        $totalPoints += $stats['debts_cleared'] * 15;
        $totalPoints += $stats['groups_joined'] * 20;
        $totalPoints += $stats['groups_created'] * 50;
        $totalPoints += $stats['expenses_created'] * 8;
        $totalPoints += $stats['trust_score'];
        $totalPoints += $stats['current_streak'] * 10;

        $pointsInCurrentLevel = $totalPoints - $pointsForCurrentLevel;
        $pointsNeededForNextLevel = 500;
        $progressPercentage = ($pointsInCurrentLevel / $pointsNeededForNextLevel) * 100;

        return [
            'current_points' => $pointsInCurrentLevel,
            'points_needed' => $pointsNeededForNextLevel,
            'progress_percentage' => round($progressPercentage, 2),
        ];
    }

    /**
     * Get group analytics.
     */
    public function getGroupAnalytics(Group $group): array
    {
        $expenses = $group->expenses;

        return [
            'total_expenses' => $expenses->count(),
            'total_amount' => $expenses->sum('amount'),
            'average_expense' => $expenses->avg('amount'),
            'fully_paid_expenses' => $expenses->where('status', 'fully_paid')->count(),
            'pending_expenses' => $expenses->where('status', 'pending')->count(),
            'most_active_payer' => $this->getMostActivePayer($group),
            'expense_trend' => $this->getExpenseTrend($group),
            'payment_completion_rate' => $this->getPaymentCompletionRate($group),
            'average_settlement_time' => $this->getAverageSettlementTime($group),
        ];
    }

    /**
     * Get most active payer in group.
     */
    private function getMostActivePayer(Group $group): ?array
    {
        $payerStats = $group->expenses()
            ->select('payer_id', DB::raw('COUNT(*) as expense_count'), DB::raw('SUM(amount) as total_amount'))
            ->groupBy('payer_id')
            ->orderBy('expense_count', 'desc')
            ->first();

        if (!$payerStats) {
            return null;
        }

        $user = User::find($payerStats->payer_id);

        return [
            'user' => $user,
            'expense_count' => $payerStats->expense_count,
            'total_amount' => $payerStats->total_amount,
        ];
    }

    /**
     * Get expense trend over time.
     */
    private function getExpenseTrend(Group $group): array
    {
        $trend = $group->expenses()
            ->select(
                DB::raw('DATE_FORMAT(date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        return $trend->toArray();
    }

    /**
     * Get payment completion rate for group.
     */
    private function getPaymentCompletionRate(Group $group): float
    {
        $totalSplits = 0;
        $paidSplits = 0;

        foreach ($group->expenses as $expense) {
            $totalSplits += $expense->splits()->count();
            $paidSplits += $expense->splits()
                ->whereHas('payment', function ($q) {
                    $q->where('status', 'paid');
                })
                ->count();
        }

        return $totalSplits > 0 ? round(($paidSplits / $totalSplits) * 100, 2) : 0;
    }

    /**
     * Get average time to settle payments.
     */
    private function getAverageSettlementTime(Group $group): ?float
    {
        $settlements = DB::table('payments')
            ->join('expense_splits', 'payments.expense_split_id', '=', 'expense_splits.id')
            ->join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expenses.group_id', $group->id)
            ->where('payments.status', 'paid')
            ->whereNotNull('payments.paid_date')
            ->select(DB::raw('DATEDIFF(payments.paid_date, expenses.date) as days_to_settle'))
            ->get();

        if ($settlements->isEmpty()) {
            return null;
        }

        return round($settlements->avg('days_to_settle'), 1);
    }
}
