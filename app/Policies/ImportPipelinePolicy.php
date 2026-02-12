<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Elaitech\Import\Models\ImportPipeline;

class ImportPipelinePolicy
{
    /**
     * Determine whether the user can view any pipelines.
     * Global scope handles filtering, so any authenticated user can list.
     */
    public function viewAny(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    /**
     * Determine whether the user can view the pipeline.
     */
    public function view(User $user, ImportPipeline $pipeline): bool
    {
        return $pipeline->organization_uuid === $user->organization_uuid;
    }

    /**
     * Determine whether the user can create pipelines.
     */
    public function create(User $user): bool
    {
        return (bool) $user->organization_uuid;
    }

    /**
     * Determine whether the user can update the pipeline.
     */
    public function update(User $user, ImportPipeline $pipeline): bool
    {
        return $pipeline->organization_uuid === $user->organization_uuid;
    }

    /**
     * Determine whether the user can delete the pipeline.
     */
    public function delete(User $user, ImportPipeline $pipeline): bool
    {
        return $pipeline->organization_uuid === $user->organization_uuid;
    }

    /**
     * Determine whether the user can export the pipeline.
     */
    public function export(User $user, ImportPipeline $pipeline): bool
    {
        return $pipeline->organization_uuid === $user->organization_uuid;
    }

    /**
     * Determine whether the user can import pipelines.
     */
    public function import(User $user, ImportPipeline $pipeline): bool
    {
        return (bool) $user->organization_uuid;
    }
}
