<?php

declare(strict_types=1);

namespace App\Http\ViewModels;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class PipelineStatsViewModel extends ViewModel
{
    public function __construct(
        private readonly int $active = 0,
        private readonly int $successful = 0,
        private readonly int $failed = 0,
        private readonly int $running = 0,
    ) {}

    public function active(): int
    {
        return $this->active;
    }

    public function successful(): int
    {
        return $this->successful;
    }

    public function failed(): int
    {
        return $this->failed;
    }

    public function running(): int
    {
        return $this->running;
    }

    public function total(): int
    {
        return $this->active + $this->successful + $this->failed + $this->running;
    }

    public function successRate(): float
    {
        $total = $this->total();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->successful / $total) * 100, 2);
    }

    public function failureRate(): float
    {
        $total = $this->total();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->failed / $total) * 100, 2);
    }
}
