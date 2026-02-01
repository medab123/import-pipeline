<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class TestDataMapperViewModel extends ViewModel
{
    public function __construct(
        private readonly array $testResult
    ) {}

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create from mapper result data.
     */
    public static function fromMapperResult(array $result): self
    {
        return new self([
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Test completed',
            'details' => $result['details'] ?? null,
        ]);
    }

    /**
     * Check if test was successful.
     */
    public function isSuccess(): bool
    {
        return $this->testResult['success'] ?? false;
    }

    /**
     * Get test message.
     */
    public function getMessage(): string
    {
        return $this->testResult['message'] ?? 'Test completed';
    }

    /**
     * Get test details.
     */
    public function getDetails(): ?array
    {
        return $this->testResult['details'] ?? null;
    }

    /**
     * Get the full test result.
     */
    public function testResult(): array
    {
        return $this->testResult;
    }
}
