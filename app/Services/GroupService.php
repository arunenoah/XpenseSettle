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
        $members = $group->members;
        $balances = [];

        foreach ($members as $member) {
            $totalOwed = 0;
            $totalPaid = 0;

            // Get amount owed by this member
            foreach ($group->expenses as $expense) {
                if ($expense->split_type === 'itemwise') {
                    // For itemwise: only count if member is the payer (they paid the full amount)
                    if ($member->id === $expense->payer_id) {
                        $totalPaid += $expense->amount;
                    }
                } else {
                    // For equal/custom splits: use the splits table
                    $split = $expense->splits()->where('user_id', $member->id)->first();
                    if ($split) {
                        $totalOwed += $split->share_amount;
                    }
                }
            }

            // Get amount paid by this member (for non-itemwise only)
            if ($totalPaid === 0) {
                $totalPaid = $member->paidExpenses()
                    ->where('group_id', $group->id)
                    ->where('split_type', '!=', 'itemwise')
                    ->sum('amount');
            }

            $balances[$member->id] = [
                'user' => $member,
                'total_owed' => $totalOwed,
                'total_paid' => $totalPaid,
                'net_balance' => $totalPaid - $totalOwed,
            ];
        }

        return $balances;
    }
}
