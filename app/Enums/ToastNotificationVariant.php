<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ToastNotificationVariant: string
{
    case Destructive = 'destructive';
    case Default = 'default';
}
