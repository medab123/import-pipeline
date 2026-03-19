<?php

declare(strict_types=1);

namespace App\Http\ViewModels;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class PipelineStatsViewModel extends ViewModel
{
    public function __construct(
        private readonly int $total = 0,
        private readonly int $active = 0,
        private readonly int $inactive = 0,
        private readonly int $needsConfiguration = 0,
    ) {}

    public function total(): int
    {
        return $this->total;
    }

    public function active(): int
    {
        return $this->active;
    }

    public function inactive(): int
    {
        return $this->inactive;
    }

    public function needsConfiguration(): int
    {
        return $this->needsConfiguration;
    }
}
