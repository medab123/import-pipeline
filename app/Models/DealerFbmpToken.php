<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerFbmpToken extends Model
{
    protected $fillable = [
        'dealer_id',
        'organization_uuid',
        'token',
        'user_email',
        'limit_account',
    ];

    protected function casts(): array
    {
        return [
            'limit_account' => 'integer',
        ];
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }
}
