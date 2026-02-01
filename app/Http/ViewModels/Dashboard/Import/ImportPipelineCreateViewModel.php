<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Illuminate\Database\Eloquent\Collection;
use Spatie\ViewModels\ViewModel;

final class ImportPipelineCreateViewModel extends ViewModel
{
    public function __construct(
        private readonly ImportDashboardServiceInterface $dashboardService
    ) {}

    public function templates(): array
    {
        return $this->dashboardService->getTemplates(true)->toArray();
    }

    public function frequencies(): array
    {
        return ImportPipelineFrequency::getOptions();
    }

    public function frequencyOptions(): array
    {
        return ImportPipelineFrequency::getOptions();
    }

    public function defaultFrequency(): string
    {
        return ImportPipelineFrequency::ONCE->value;
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
