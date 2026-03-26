<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationToken extends Model
{
    protected $fillable = [
        'organization_uuid',
        'name',
        'description',
        'token',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * The organization this token belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }
}
