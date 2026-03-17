<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DealerStatus;
use App\Enums\PaymentPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_uuid',
        'name',
        'status',
        'notes',
        'posting_address',
        'website_url',
        'fbmp_app_access_token',
        'fbmp_app_url',
        'payment_period',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealerStatus::class,
            'payment_period' => PaymentPeriod::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function scraps(): HasMany
    {
        return $this->hasMany(Scrap::class);
    }

    /**
     * Automatically set status to active when dealer has both a scrap source
     * and an FBMP token, otherwise revert to pending.
     */
    public function resolveStatus(): void
    {
        $hasScrapSource = $this->scraps()->exists();
        $hasFbmpToken = ! empty($this->fbmp_app_access_token);

        if ($hasScrapSource && $hasFbmpToken) {
            if ($this->status === DealerStatus::Pending) {
                $this->updateQuietly(['status' => DealerStatus::Active->value]);
            }
        } else {
            if ($this->status !== DealerStatus::Pending) {
                $this->updateQuietly(['status' => DealerStatus::Pending->value]);
            }
        }
    }
}
