<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum PaymentTransactionType: string
{
    case DealerPayment = 'dealer_payment';
    case FbmpPayment = 'fbmp_payment';
}
