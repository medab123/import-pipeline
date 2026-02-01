<?php

namespace App\Http\ViewModels\Dashboard\Import\Stepper;

use Elaitech\Import\Enums\ImportPipelineStep;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class StepViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportPipelineStep $step,
        private readonly string $route,
        private readonly bool $isAvailable,
        private readonly int $index,
        private readonly string $title,
        private readonly string $description,
    ) {
        //
    }

    public function step(): ImportPipelineStep
    {
        return $this->step;
    }

    public function route(): string
    {
        return $this->route;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function index(): int
    {
        return $this->index;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }
}
