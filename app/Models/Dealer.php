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
        'website_urls',
        'fbmp_app_url',
        'payment_period',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealerStatus::class,
            'payment_period' => PaymentPeriod::class,
            'website_urls' => 'array',
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

    public function fbmpTokens(): HasMany
    {
        return $this->hasMany(DealerFbmpToken::class)->orderBy('id');
    }

    /**
     * Automatically set status to active when dealer has both a scrap source
     * and at least one FBMP token, otherwise revert to pending.
     */
    public function resolveStatus(): void
    {
        $hasScrapSource = $this->scraps()->exists();
        $hasFbmpToken = $this->fbmpTokens()->exists();

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
