<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\PaymentTransaction;

use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Models\Dealer;
use App\Models\PaymentTransaction;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class EditPaymentTransactionViewModel extends ViewModel
{
    public function __construct(private readonly PaymentTransaction $transaction) {}

    public function transaction(): PaymentTransactionViewModel
    {
        return new PaymentTransactionViewModel($this->transaction);
    }

    public function dealers(): array
    {
        return Dealer::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }

    public function types(): array
    {
        return array_map(
            fn (PaymentTransactionType $type) => ['value' => $type->value, 'label' => str_replace('_', ' ', ucwords($type->value, '_'))],
            PaymentTransactionType::cases()
        );
    }

    public function statuses(): array
    {
        return array_map(
            fn (PaymentTransactionStatus $status) => ['value' => $status->value, 'label' => ucfirst($status->value)],
            PaymentTransactionStatus::cases()
        );
    }
}
