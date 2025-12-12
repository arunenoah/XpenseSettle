<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

class GroupMemberService
{
    /**
     * Add a contact as a group member.
     */
    public function addContactMember(Group $group, string $name, ?string $email = null, ?string $phone = null, string $role = 'member', int $familyCount = 0): GroupMember
    {
        // Build the lookup criteria for finding existing contact
        $lookupCriteria = ['group_id' => $group->id];

        // Only include email in lookup if it's provided (not null)
        if ($email) {
            $lookupCriteria['email'] = $email;
        } else {
            // If no email, lookup by name and phone combination
            $lookupCriteria['name'] = $name;
            $lookupCriteria['phone'] = $phone;
        }

        // Create or find contact
        $contact = Contact::firstOrCreate(
            $lookupCriteria,
            ['name' => $name, 'phone' => $phone, 'family_count' => $familyCount]
        );

        // If contact already exists, update family_count and other details
        if ($contact->wasRecentlyCreated === false) {
            $contact->update([
                'family_count' => $familyCount,
                'phone' => $phone ?? $contact->phone,
            ]);
        }

        // Add to group members if not already exists
        return GroupMember::firstOrCreate(
            ['group_id' => $group->id, 'contact_id' => $contact->id],
            ['role' => $role]
        );
    }

    /**
     * Add a user as a group member.
     */
    public function addUserMember(Group $group, int $userId, string $role = 'member'): GroupMember
    {
        return GroupMember::firstOrCreate(
            ['group_id' => $group->id, 'user_id' => $userId],
            ['role' => $role]
        );
    }

    /**
     * Remove a member from group.
     */
    public function removeMember(Group $group, ?int $userId = null, ?int $contactId = null): bool
    {
        return GroupMember::where('group_id', $group->id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($contactId, fn($q) => $q->where('contact_id', $contactId))
            ->delete() > 0;
    }

    /**
     * Get all splittable members (users + contacts).
     */
    public function getSplittableMembers(Group $group): array
    {
        return $group->allMembers()
            ->get()
            ->map(fn($member) => [
                'id' => $member->id,
                'type' => $member->isActiveUser() ? 'user' : 'contact',
                'name' => $member->getMemberName(),
                'user_id' => $member->user_id,
                'contact_id' => $member->contact_id,
                'role' => $member->role,
            ])
            ->toArray();
    }

    /**
     * Get member by ID (GroupMember ID).
     */
    public function getMember(int $groupMemberId)
    {
        return GroupMember::with(['user', 'contact'])->find($groupMemberId);
    }

    /**
     * Update member role.
     */
    public function updateMemberRole(int $groupMemberId, string $role): bool
    {
        return GroupMember::where('id', $groupMemberId)->update(['role' => $role]) > 0;
    }

    /**
     * Check if contact exists in group by email.
     */
    public function contactExistsInGroup(Group $group, ?string $email): bool
    {
        if (!$email) {
            return false;
        }

        return Contact::where('group_id', $group->id)
            ->where('email', $email)
            ->exists();
    }

    /**
     * Get contact by email in group.
     */
    public function getContactByEmail(Group $group, string $email): ?Contact
    {
        return Contact::where('group_id', $group->id)
            ->where('email', $email)
            ->first();
    }

    /**
     * Update contact details.
     */
    public function updateContact(int $contactId, array $data): Contact
    {
        $contact = Contact::findOrFail($contactId);
        $contact->update($data);
        return $contact;
    }
}
