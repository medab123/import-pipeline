<?php

declare(strict_types=1);

namespace App\Models;

use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

final class OrganizationToken extends Model
{
    use HasFactory;

    /**
     * Retrieve the model for route model binding.
     * Automatically scopes to the current organization context.
     *
     * Note: This runs before middleware, so we get organization from the authenticated user.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $user = Auth::user();

        if (! $user || ! $user->organization_uuid) {
            return null;
        }

        return $this->where('id', $value)
            ->where('organization_uuid', $user->organization_uuid)
            ->first();
    }

    protected $fillable = [
        'organization_uuid',
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }

    /**
     * Get the pipelines that this token can access.
     */
    public function pipelines(): BelongsToMany
    {
        return $this->belongsToMany(
            ImportPipeline::class,
            'organization_token_pipeline',
            'organization_token_id',
            'pipeline_id'
        )->withTimestamps();
    }

    /**
     * Find a token by its plain text value.
     */
    public static function findByPlainTextToken(string $plainTextToken): ?self
    {
        $hashedToken = hash('sha256', $plainTextToken);

        return static::where('token', $hashedToken)->first();
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (not expired).
     */
    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if the token can access a specific pipeline.
     * If the token has no pipelines assigned, it can access all pipelines (backward compatibility).
     * Otherwise, it can only access the assigned pipelines.
     */
    public function canAccessPipeline(ImportPipeline $pipeline): bool
    {
        return $this->canAccessPipelineById($pipeline->id);
    }

    /**
     * Check if the token can access a pipeline by ID.
     * If the token has no pipelines assigned, it can access all pipelines (backward compatibility).
     * Otherwise, it can only access the assigned pipelines.
     */
    public function canAccessPipelineById(int $pipelineId): bool
    {
        // If no pipelines are assigned, allow access to all (backward compatibility)
        if ($this->pipelines()->count() === 0) {
            return true;
        }

        // Check if the pipeline ID is in the allowed list
        // Query the related model's id column, not the pivot table column
        return $this->pipelines()->where('import_pipelines.id', $pipelineId)->exists();
    }
}
