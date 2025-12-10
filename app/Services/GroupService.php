<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GroupService
{
    /**
     * Create a new group.
     *
     * @param User $user
     * @param array $data
     * @return Group
     */
    public function createGroup(User $user, array $data): Group
    {
        $group = Group::create([
            'created_by' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
        ]);

        // Add creator as an admin member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        return $group;
    }

    /**
     * Update a group.
     *
     * @param Group $group
     * @param array $data
     * @return Group
     */
    public function updateGroup(Group $group, array $data): Group
    {
        $group->update([
            'name' => $data['name'] ?? $group->name,
            'description' => $data['description'] ?? $group->description,
            'currency' => $data['currency'] ?? $group->currency,
        ]);

        return $group;
    }

    /**
     * Add a member to the group.
     *
     * @param Group $group
     * @param string $email
     * @param string $role
     * @return GroupMember
     * @throws ModelNotFoundException
     */
    public function addMember(Group $group, string $email, string $role = 'member'): GroupMember
    {
        $user = User::where('email', $email)->firstOrFail();

        // Check if user is already a member
        if ($group->hasMember($user)) {
            throw new \Exception("User is already a member of this group");
        }

        return GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    /**
     * Remove a member from the group.
     *
     * @param Group $group
     * @param User $user
     * @return bool
     */
    public function removeMember(Group $group, User $user): bool
    {
        return GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Update member role.
     *
     * @param Group $group
     * @param User $user
     * @param string $role
     * @return GroupMember|null
     */
    public function updateMemberRole(Group $group, User $user, string $role): ?GroupMember
    {
        return GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->update(['role' => $role]) > 0
                ? GroupMember::where('group_id', $group->id)->where('user_id', $user->id)->first()
                : null;
    }

    /**
     * Delete a group.
     *
     * @param Group $group
     * @return bool
     */
    public function deleteGroup(Group $group): bool
    {
        return $group->delete();
    }

    /**
     * Get group balance summary.
     *
     * @param Group $group
     * @return array
     */
    public function getGroupBalance(Group $group): array
    {
        $balances = [];

        // Get all members (users and contacts via GroupMember)
        $groupMembers = $group->allMembers()->with(['user', 'contact'])->get();

        foreach ($groupMembers as $groupMember) {
            $totalOwed = 0;
            $totalPaid = 0;

            // Determine if this is a user or contact
            $isContact = $groupMember->isContact();
            $memberObj = $isContact ? $groupMember->contact : $groupMember->user;

            if (!$memberObj) {
                continue; // Skip if neither user nor contact found
            }

            // Get amount owed by this member via expense splits
            foreach ($group->expenses as $expense) {
                if ($expense->split_type === 'itemwise') {
                    // For itemwise: only count if member is the payer (they paid the full amount)
                    // Contacts can't be payers
                    if (!$isContact && $memberObj->id === $expense->payer_id) {
                        $totalPaid += $expense->amount;
                    }
                } else {
                    // For equal/custom splits: find split by user_id or contact_id
                    if ($isContact) {
                        $split = $expense->splits()->where('contact_id', $groupMember->contact_id)->first();
                    } else {
                        $split = $expense->splits()->where('user_id', $groupMember->user_id)->first();
                    }

                    if ($split) {
                        $totalOwed += $split->share_amount;
                    }
                }
            }

            // Only calculate paid amounts for actual users (not contacts)
            if (!$isContact) {
                // Get amount paid by this member as payer of expenses (for non-itemwise only)
                if ($totalPaid === 0) {
                    $totalPaid = $memberObj->paidExpenses()
                        ->where('group_id', $group->id)
                        ->where('split_type', '!=', 'itemwise')
                        ->sum('amount');
                }

                // Add amount this member has paid back (marked as paid in Payment records)
                $paidBackAmount = \App\Models\Payment::whereHas('split', function ($q) use ($groupMember, $group) {
                    $q->where('user_id', $groupMember->user_id)
                      ->whereHas('expense', function ($q2) use ($group) {
                          $q2->where('group_id', $group->id);
                      });
                })
                ->where('status', 'paid')
                ->join('expense_splits', 'payments.expense_split_id', '=', 'expense_splits.id')
                ->sum('expense_splits.share_amount');

                $totalPaid += $paidBackAmount;
            }

            $balances[$groupMember->id] = [
                'user' => $memberObj,
                'is_contact' => $isContact,
                'total_owed' => $totalOwed,
                'total_paid' => $totalPaid,
                'net_balance' => $totalPaid - $totalOwed,
            ];
        }

        return $balances;
    }
}
