<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\DataMapper\Transformers;

use Elaitech\Import\Services\DataMapper\Contracts\TransformerInterface;

class UpperTransformer implements TransformerInterface
{
    public function getName(): string
    {
        return 'upper';
    }

    public function getLabel(): string
    {
        return 'Uppercase';
    }

    public function getDescription(): string
    {
        return 'Convert string to uppercase';
    }

    public function transform($value, ?string $format = null, $defaultValue = null)
    {
        return is_string($value) ? strtoupper(trim($value)) : $value;
    }

    public function requiresFormat(): bool
    {
        return false;
    }
}
