<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Enums\PaymentPeriod;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class CreateDealerViewModel extends ViewModel
{
    public function paymentPeriods(): array
    {
        return array_map(
            fn (PaymentPeriod $period) => ['value' => $period->value, 'label' => ucfirst($period->value)],
            PaymentPeriod::cases()
        );
    }
}
