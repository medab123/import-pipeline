<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class TestFilterViewModel extends ViewModel
{
    public function __construct(
        private readonly array $testResult
    ) {}

    public static function fromFilterResult(array $result): self
    {
        return new self($result);
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function isSuccess(): bool
    {
        return $this->testResult['success'] ?? false;
    }

    public function getMessage(): string
    {
        return $this->testResult['message'] ?? 'Unknown result';
    }

    public function testResult(): array
    {
        return $this->testResult;
    }

    public function getDetails(): array
    {
        return $this->testResult['details'] ?? [];
    }
}
