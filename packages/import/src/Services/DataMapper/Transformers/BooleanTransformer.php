<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\DataMapper\Transformers;

use Elaitech\Import\Services\DataMapper\Contracts\TransformerInterface;

class BooleanTransformer implements TransformerInterface
{
    public function getName(): string
    {
        return 'bool';
    }

    public function getLabel(): string
    {
        return 'Boolean';
    }

    public function getDescription(): string
    {
        return 'Convert value to boolean';
    }

    public function transform($value, ?string $format = null, $defaultValue = null): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function requiresFormat(): bool
    {
        return false;
    }
}
