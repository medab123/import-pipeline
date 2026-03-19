<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dealer;
use App\Models\User;

class DealerPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_uuid
            && $user->can('view dealers');
    }

    public function view(User $user, Dealer $dealer): bool
    {
        return $dealer->organization_uuid === $user->organization_uuid
            && $user->can('view dealers');
    }

    public function create(User $user): bool
    {
        return (bool) $user->organization_uuid
            && $user->can('manage dealers');
    }

    public function update(User $user, Dealer $dealer): bool
    {
        return $dealer->organization_uuid === $user->organization_uuid
            && $user->can('manage dealers');
    }

    public function delete(User $user, Dealer $dealer): bool
    {
        return $dealer->organization_uuid === $user->organization_uuid
            && $user->can('manage dealers');
    }
}
