<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Enums\DealerStatus;
use App\Enums\PaymentPeriod;
use App\Models\Dealer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class EditDealerViewModel extends ViewModel
{
    public function __construct(private readonly Dealer $dealer) {}

    public function dealer(): DealerViewModel
    {
        return new DealerViewModel($this->dealer);
    }

    public function statuses(): array
    {
        return array_map(
            fn (DealerStatus $status) => ['value' => $status->value, 'label' => ucfirst($status->value)],
            DealerStatus::cases()
        );
    }

    public function paymentPeriods(): array
    {
        return array_map(
            fn (PaymentPeriod $period) => ['value' => $period->value, 'label' => ucfirst($period->value)],
            PaymentPeriod::cases()
        );
    }
}
