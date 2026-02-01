<?php

declare(strict_types=1);

namespace App\Factories\ImportPipeline\Steps;

use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\PreviewStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

final class PreviewStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return resolve(PreviewStepViewModel::class, ['pipeline' => $pipeline]);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        return $pipeline->fresh();
    }

    /**
     * Get the validation rules for the step.
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        return ! empty($pipeline->getMapperConfig());
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Pipeline configuration done saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/PreviewStep';
    }
}
