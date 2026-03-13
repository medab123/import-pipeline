<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\PaymentTransaction;

use App\Models\PaymentTransaction;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ShowPaymentTransactionViewModel extends ViewModel
{
    public function __construct(private readonly PaymentTransaction $transaction) {}

    public function transaction(): PaymentTransactionViewModel
    {
        return new PaymentTransactionViewModel($this->transaction);
    }
}
