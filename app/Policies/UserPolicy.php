<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * Determine whether the user can view org members.
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_uuid !== null
            && $user->can('manage users');
    }

    /**
     * Determine whether the user can create org members.
     */
    public function create(User $user): bool
    {
        return $user->organization_uuid !== null
            && $user->can('manage users');
    }

    /**
     * Determine whether the user can update the given org member.
     */
    public function update(User $user, User $target): bool
    {
        return $user->organization_uuid !== null
            && $user->can('manage users')
            && $user->organization_uuid === $target->organization_uuid;
    }

    /**
     * Determine whether the user can delete the given org member.
     * Users cannot delete themselves.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->organization_uuid !== null
            && $user->can('manage users')
            && $user->organization_uuid === $target->organization_uuid
            && $user->id !== $target->id;
    }
}
