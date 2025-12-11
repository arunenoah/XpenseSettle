<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit trail
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        string $description = '',
        ?int $groupId = null,
        ?array $changes = null,
        string $status = 'success',
        ?string $errorMessage = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'group_id' => $groupId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Log a successful action
     */
    public function logSuccess(
        string $action,
        string $entityType,
        string $description,
        ?int $entityId = null,
        ?int $groupId = null,
        ?array $changes = null
    ): AuditLog {
        return $this->log($action, $entityType, $entityId, $description, $groupId, $changes, 'success');
    }

    /**
     * Log a failed action
     */
    public function logFailed(
        string $action,
        string $entityType,
        string $description,
        string $errorMessage,
        ?int $entityId = null,
        ?int $groupId = null
    ): AuditLog {
        return $this->log($action, $entityType, $entityId, $description, $groupId, null, 'failed', $errorMessage);
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): AuditLog
    {
        return $this->logSuccess(
            'login',
            'User',
            "{$user->name} logged in",
            $user->id,
            null
        );
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->logSuccess(
            'logout',
            'User',
            "{$user->name} logged out",
            $user->id,
            null
        );
    }

    /**
     * Log group creation
     */
    public function logGroupCreated($group): AuditLog
    {
        return $this->logSuccess(
            'create_group',
            'Group',
            "Group '{$group->name}' created",
            $group->id,
            $group->id
        );
    }

    /**
     * Log group update
     */
    public function logGroupUpdated($group, array $changes): AuditLog
    {
        return $this->logSuccess(
            'update_group',
            'Group',
            "Group '{$group->name}' updated",
            $group->id,
            $group->id,
            $changes
        );
    }

    /**
     * Log group deletion
     */
    public function logGroupDeleted($group): AuditLog
    {
        return $this->logSuccess(
            'delete_group',
            'Group',
            "Group '{$group->name}' deleted",
            $group->id,
            $group->id
        );
    }

    /**
     * Log member addition
     */
    public function logMemberAdded($group, $member): AuditLog
    {
        $memberName = $member->user ? $member->user->name : $member->contact->name;
        return $this->logSuccess(
            'add_member',
            'GroupMember',
            "Member '{$memberName}' added to group '{$group->name}'",
            $member->id,
            $group->id
        );
    }

    /**
     * Log member removal
     */
    public function logMemberRemoved($group, $member): AuditLog
    {
        $memberName = $member->user ? $member->user->name : $member->contact->name;
        return $this->logSuccess(
            'remove_member',
            'GroupMember',
            "Member '{$memberName}' removed from group '{$group->name}'",
            $member->id,
            $group->id
        );
    }

    /**
     * Log contact addition
     */
    public function logContactAdded($group, $contact): AuditLog
    {
        return $this->logSuccess(
            'add_contact',
            'Contact',
            "Contact '{$contact->name}' added to group '{$group->name}'",
            $contact->id,
            $group->id
        );
    }

    /**
     * Log expense creation
     */
    public function logExpenseCreated($group, $expense): AuditLog
    {
        return $this->logSuccess(
            'create_expense',
            'Expense',
            "Expense '{$expense->title}' (₹{$expense->amount}) created in group '{$group->name}'",
            $expense->id,
            $group->id
        );
    }

    /**
     * Log expense update
     */
    public function logExpenseUpdated($group, $expense, array $changes): AuditLog
    {
        return $this->logSuccess(
            'update_expense',
            'Expense',
            "Expense '{$expense->title}' updated in group '{$group->name}'",
            $expense->id,
            $group->id,
            $changes
        );
    }

    /**
     * Log expense deletion
     */
    public function logExpenseDeleted($group, $expenseTitle): AuditLog
    {
        return $this->logSuccess(
            'delete_expense',
            'Expense',
            "Expense '{$expenseTitle}' deleted from group '{$group->name}'",
            null,
            $group->id
        );
    }

    /**
     * Log payment marked as paid
     */
    public function logPaymentMarked($group, $payment, $expense): AuditLog
    {
        $user = $payment->split->user;
        return $this->logSuccess(
            'mark_paid',
            'Payment',
            "{$user->name} marked payment of ₹{$payment->split->share_amount} as paid for '{$expense->title}' in group '{$group->name}'",
            $payment->id,
            $group->id
        );
    }

    /**
     * Log payment approved
     */
    public function logPaymentApproved($group, $payment, $expense): AuditLog
    {
        $user = $payment->split->user;
        return $this->logSuccess(
            'approve_payment',
            'Payment',
            "Payment of ₹{$payment->split->share_amount} from {$user->name} for '{$expense->title}' approved in group '{$group->name}'",
            $payment->id,
            $group->id
        );
    }

    /**
     * Log payment rejected
     */
    public function logPaymentRejected($group, $payment, $expense, $reason = ''): AuditLog
    {
        $user = $payment->split->user;
        $description = "Payment of ₹{$payment->split->share_amount} from {$user->name} for '{$expense->title}' rejected";
        if ($reason) {
            $description .= " - Reason: {$reason}";
        }
        $description .= " in group '{$group->name}'";

        return $this->logSuccess(
            'reject_payment',
            'Payment',
            $description,
            $payment->id,
            $group->id
        );
    }

    /**
     * Get audit logs for a specific group
     */
    public function getGroupLogs($groupId, $perPage = 50)
    {
        return AuditLog::forGroup($groupId)
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get audit logs for a specific user
     */
    public function getUserLogs($userId, $perPage = 50)
    {
        return AuditLog::forUser($userId)
            ->with('group')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get recent activity summary
     */
    public function getGroupActivitySummary($groupId, $days = 7)
    {
        $since = now()->subDays($days);

        return [
            'total_actions' => AuditLog::forGroup($groupId)->where('created_at', '>=', $since)->count(),
            'by_action' => AuditLog::forGroup($groupId)
                ->where('created_at', '>=', $since)
                ->groupBy('action')
                ->selectRaw('action, count(*) as count')
                ->get(),
            'by_user' => AuditLog::forGroup($groupId)
                ->where('created_at', '>=', $since)
                ->with('user')
                ->groupBy('user_id')
                ->selectRaw('user_id, count(*) as count')
                ->get(),
            'failed_actions' => AuditLog::forGroup($groupId)
                ->failed()
                ->where('created_at', '>=', $since)
                ->count(),
        ];
    }
}
