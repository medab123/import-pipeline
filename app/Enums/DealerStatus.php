<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum DealerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
