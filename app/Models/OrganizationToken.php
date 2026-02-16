<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationToken extends Model
{
    use HasFactory;

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
}
