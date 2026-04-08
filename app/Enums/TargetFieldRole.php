<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TargetFieldRole: string
{
    case SerialNumber = 'serial_number';
    case Images = 'images';
}
