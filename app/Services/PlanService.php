<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;

class PlanService
{
    // Plan limits
    const FREE_OCR_LIMIT = 5;
    const FREE_ATTACHMENTS_LIMIT = 10;
    
    /**
     * Check if user has lifetime plan
     */
    public function hasLifetimePlan(User $user): bool
    {
        return $user->plan === 'lifetime';
    }
    
    /**
     * Check if group has active trip pass
     */
    public function hasActiveTripPass(Group $group): bool
    {
        if ($group->plan !== 'trip_pass') {
            return false;
        }
        
        // Check if trip pass is still valid (not expired)
        if ($group->plan_expires_at && $group->plan_expires_at->isPast()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if group can use OCR (has scans remaining)
     */
    public function canUseOCR(Group $group): bool
    {
        // Lifetime users get unlimited
        if ($this->hasLifetimePlan($group->creator)) {
            return true;
        }
        
        // Trip pass users get unlimited
        if ($this->hasActiveTripPass($group)) {
            return true;
        }
        
        // Free users get 5 scans per trip
        return $group->ocr_scans_used < self::FREE_OCR_LIMIT;
    }
    
    /**
     * Get remaining OCR scans for free users
     */
    public function getRemainingOCRScans(Group $group): int
    {
        if ($this->hasLifetimePlan($group->creator) || $this->hasActiveTripPass($group)) {
            return PHP_INT_MAX; // Unlimited
        }
        
        return max(0, self::FREE_OCR_LIMIT - $group->ocr_scans_used);
    }
    
    /**
     * Increment OCR scan counter
     */
    public function incrementOCRScan(Group $group): void
    {
        // Only increment for free users
        if (!$this->hasLifetimePlan($group->creator) && !$this->hasActiveTripPass($group)) {
            $group->increment('ocr_scans_used');
        }
    }
    
    /**
     * Check if group can add attachments
     */
    public function canAddAttachment(Group $group): bool
    {
        // Lifetime users get unlimited
        if ($this->hasLifetimePlan($group->creator)) {
            return true;
        }
        
        // Trip pass users get unlimited
        if ($this->hasActiveTripPass($group)) {
            return true;
        }
        
        // Free users get limited attachments
        $attachmentCount = $group->expenses()->withCount('attachments')->get()->sum('attachments_count');
        return $attachmentCount < self::FREE_ATTACHMENTS_LIMIT;
    }
    
    /**
     * Check if group can export PDF/Excel
     */
    public function canExportReports(Group $group): bool
    {
        return $this->hasLifetimePlan($group->creator) || $this->hasActiveTripPass($group);
    }
    
    /**
     * Activate trip pass for a group
     */
    public function activateTripPass(Group $group, int $daysValid = 365): void
    {
        $group->update([
            'plan' => 'trip_pass',
            'plan_expires_at' => now()->addDays($daysValid),
            'ocr_scans_used' => 0, // Reset counter
        ]);
    }
    
    /**
     * Activate lifetime plan for a user
     */
    public function activateLifetimePlan(User $user): void
    {
        $user->update([
            'plan' => 'lifetime',
            'plan_expires_at' => null, // Lifetime never expires
        ]);
    }
    
    /**
     * Get plan name for display
     */
    public function getPlanName(Group $group): string
    {
        if ($this->hasLifetimePlan($group->creator)) {
            return 'Lifetime';
        }
        
        if ($this->hasActiveTripPass($group)) {
            return 'Trip Pass';
        }
        
        return 'Free';
    }
    
    /**
     * Get upgrade message for feature
     */
    public function getUpgradeMessage(string $feature): string
    {
        $messages = [
            'ocr' => 'You\'ve reached your free OCR limit (5 scans). Upgrade to Trip Pass for unlimited scans!',
            'attachments' => 'You\'ve reached your free attachment limit. Upgrade to Trip Pass for unlimited attachments!',
            'export' => 'PDF/Excel export is a premium feature. Upgrade to Trip Pass or Lifetime to unlock!',
        ];
        
        return $messages[$feature] ?? 'Upgrade to unlock this premium feature!';
    }
}
