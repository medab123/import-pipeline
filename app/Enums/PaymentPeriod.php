<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum PaymentPeriod: string
{
    case Month = 'month';
    case Year = 'year';
}
