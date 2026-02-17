<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * Determine whether the user can view org members.
     * Admins can view all users.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all users
        if ($user->isAdmin()) {
            return true;
        }

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
     * Admins can update all users.
     */
    public function update(User $user, User $target): bool
    {
        // Admins can update all users
        if ($user->isAdmin()) {
            return true;
        }

        return $user->organization_uuid !== null
            && $user->can('manage users')
            && $user->organization_uuid === $target->organization_uuid;
    }

    /**
     * Determine whether the user can delete the given org member.
     * Users cannot delete themselves.
     * Admins can delete all users except themselves.
     */
    public function delete(User $user, User $target): bool
    {
        // Admins can delete all users except themselves
        if ($user->isAdmin()) {
            return $user->id !== $target->id;
        }

        return $user->organization_uuid !== null
            && $user->can('manage users')
            && $user->organization_uuid === $target->organization_uuid
            && $user->id !== $target->id;
    }
}
