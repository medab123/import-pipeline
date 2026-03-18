<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Models\Dealer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class DealerViewModel extends ViewModel
{
    public function __construct(private readonly Dealer $dealer) {}

    public function id(): int
    {
        return $this->dealer->id;
    }

    public function name(): string
    {
        return $this->dealer->name;
    }

    public function status(): string
    {
        return $this->dealer->status->value;
    }

    public function notes(): ?string
    {
        return $this->dealer->notes;
    }

    public function postingAddress(): ?string
    {
        return $this->dealer->posting_address;
    }

    public function websiteUrls(): array
    {
        return $this->dealer->website_urls ?? [];
    }

    public function fbmpAppAccessToken(): ?string
    {
        return $this->dealer->fbmp_app_access_token;
    }

    public function fbmpAppUrl(): ?string
    {
        return $this->dealer->fbmp_app_url;
    }

    public function paymentPeriod(): string
    {
        return $this->dealer->payment_period->value;
    }

    public function createdAt(): string
    {
        return $this->dealer->created_at->toISOString();
    }

    public function updatedAt(): string
    {
        return $this->dealer->updated_at->toISOString();
    }

    public function formattedCreatedAt(): string
    {
        return $this->dealer->created_at->format('M d, Y H:i');
    }

    public function formattedUpdatedAt(): string
    {
        return $this->dealer->updated_at->format('M d, Y H:i');
    }

    public function transactionsCount(): int
    {
        return $this->dealer->payment_transactions_count ?? 0;
    }

    public function scrapsCount(): int
    {
        return $this->dealer->scraps_count ?? 0;
    }

    public function isPaid(): bool
    {
        return (bool) ($this->dealer->is_paid ?? false);
    }

    public function hasFbmpToken(): bool
    {
        return ! empty($this->dealer->fbmp_app_access_token);
    }

    public function hasScrapSource(): bool
    {
        return ($this->dealer->scraps_count ?? 0) > 0;
    }
}
