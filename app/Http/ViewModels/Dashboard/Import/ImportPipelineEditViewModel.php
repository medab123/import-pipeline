<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Illuminate\Database\Eloquent\Collection;
use Spatie\ViewModels\ViewModel;

final class ImportPipelineEditViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportDashboardServiceInterface $dashboardService,
        private readonly int $pipelineId
    ) {}

    public function pipeline(): ?array
    {
        $pipeline = $this->dashboardService->getPipeline($this->pipelineId);

        if (! $pipeline) {
            return null;
        }

        return [
            'id' => $pipeline->id,
            'name' => $pipeline->name,
            'description' => $pipeline->description,
            'target_id' => $pipeline->target_id,
            'company' => $pipeline->company?->name,
            'frequency' => $pipeline->frequency->value,
            'frequency_label' => $pipeline->frequency->getLabel(),
            'start_time' => $pipeline->formatted_start_time,
            'is_active' => $pipeline->is_active,
            'created_by' => $pipeline->creator?->name,
            'created_at' => $pipeline->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $pipeline->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function frequencies(): array
    {
        return ImportPipelineFrequency::getOptions();
    }

    public function frequencyOptions(): array
    {
        return ImportPipelineFrequency::getOptions();
    }

    public function frequencyLabels(): array
    {
        return collect(ImportPipelineFrequency::getOptions())
            ->pluck('label', 'value')
            ->toArray();
    }

    public function frequencyDescriptions(): array
    {
        return collect(ImportPipelineFrequency::getOptions())
            ->pluck('description', 'value')
            ->toArray();
    }

    public function currentFrequency(): string
    {
        $pipeline = $this->dashboardService->getPipeline($this->pipelineId);

        return $pipeline?->frequency->value ?? ImportPipelineFrequency::ONCE->value;
    }

    public function isScheduled(): bool
    {
        $pipeline = $this->dashboardService->getPipeline($this->pipelineId);

        return $pipeline?->is_scheduled ?? false;
    }

    public function requiresTime(): bool
    {
        $pipeline = $this->dashboardService->getPipeline($this->pipelineId);

        return $pipeline?->requiresTime() ?? false;
    }

    public function companies(): Collection
    {
        return $this->dashboardService->getAllCompanies();
    }

    public function companyOptions(): array
    {
        return $this->companies()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'status' => $company->status,
            ];
        })->toArray();
    }
}
