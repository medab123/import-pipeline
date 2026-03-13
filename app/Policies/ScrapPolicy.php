<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Scrap;
use App\Models\User;

class ScrapPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    public function view(User $user, Scrap $scrap): bool
    {
        return $scrap->organization_uuid === $user->organization_uuid;
    }

    public function create(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    public function update(User $user, Scrap $scrap): bool
    {
        return $scrap->organization_uuid === $user->organization_uuid;
    }

    public function delete(User $user, Scrap $scrap): bool
    {
        return $scrap->organization_uuid === $user->organization_uuid;
    }
}
