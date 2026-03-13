<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\PaymentTransaction;

use App\Models\PaymentTransaction;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class PaymentTransactionViewModel extends ViewModel
{
    public function __construct(private readonly PaymentTransaction $transaction) {}

    public function id(): int
    {
        return $this->transaction->id;
    }

    public function dealerId(): int
    {
        return $this->transaction->dealer_id;
    }

    public function dealerName(): string
    {
        return $this->transaction->dealer?->name ?? '';
    }

    public function type(): string
    {
        return $this->transaction->type->value;
    }

    public function amount(): string
    {
        return $this->transaction->amount;
    }

    public function status(): string
    {
        return $this->transaction->status->value;
    }

    public function paymentMethod(): ?string
    {
        return $this->transaction->payment_method;
    }

    public function reference(): ?string
    {
        return $this->transaction->reference;
    }

    public function paidAt(): ?string
    {
        return $this->transaction->paid_at?->toISOString();
    }

    public function formattedPaidAt(): ?string
    {
        return $this->transaction->paid_at?->format('M d, Y H:i');
    }

    public function createdAt(): string
    {
        return $this->transaction->created_at->toISOString();
    }

    public function formattedCreatedAt(): string
    {
        return $this->transaction->created_at->format('M d, Y H:i');
    }
}
