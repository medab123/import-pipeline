<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use App\Http\ViewModels\CompanyViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Carbon\Carbon;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class PipelineViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportPipeline $pipeline,
    ) {}

    public function id(): int
    {
        return $this->pipeline->id;
    }

    public function name(): string
    {
        return $this->pipeline->name;
    }

    public function description(): ?string
    {
        return $this->pipeline->description;
    }

    public function targetId(): int|string
    {
        return $this->pipeline->target_id;
    }

    public function frequency(): ?string
    {
        return $this->pipeline->frequency->value;
    }

    public function startTime(): ?string
    {
        return $this->pipeline->start_time?->format('H:i') ?? now()->format('H:i');
    }

    public function formattedStartTime(): ?string
    {
        return $this->pipeline->formatted_start_time;
    }

    public function isActive(): bool
    {
        return $this->pipeline->is_active;
    }

    public function status(): array
    {
        return $this->pipeline->status->toBadgeConfig();
    }

    public function createdBy(): ?string
    {
        return $this->pipeline->creator?->name;
    }

    public function updatedBy(): ?string
    {
        return $this->pipeline->updater?->name;
    }

    public function createdAt(): ?Carbon
    {
        return $this->pipeline->created_at;
    }

    public function updatedAt(): ?Carbon
    {
        return $this->pipeline->updated_at;
    }

    public function formattedCreatedAt(): ?string
    {
        return $this->pipeline->created_at?->format('Y-m-d H:i:s');
    }

    public function formattedUpdatedAt(): ?string
    {
        return $this->pipeline->updated_at?->format('Y-m-d H:i:s');
    }

    public function lastExecutedAt(): ?Carbon
    {
        return $this->pipeline->last_executed_at;
    }

    public function formattedLastExecutedAt(): ?string
    {
        return $this->pipeline->last_executed_at?->format('Y-m-d H:i:s');
    }

    public function nextExecutionAt(): ?Carbon
    {
        return $this->pipeline->next_execution_at;
    }

    public function formattedNextExecutionAt(): ?string
    {
        return $this->pipeline->next_execution_at?->format('Y-m-d H:i:s');
    }

    public function config()
    {
        return $this->pipeline->config;
    }
}
