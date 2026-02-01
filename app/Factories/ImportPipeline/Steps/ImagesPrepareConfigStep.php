<?php

declare(strict_types=1);

namespace App\Factories\ImportPipeline\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\ImagesPrepareConfigStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

final class ImagesPrepareConfigStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new ImagesPrepareConfigStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->config()->updateOrCreate(
            ['type' => ImportPipelineStep::ImagesPrepareConfig->value],
            ['config_data' => $data]
        );

        return $pipeline->fresh();
    }

    /**
     * Get the validation rules for the step.
     */
    public function getValidationRules(): array
    {
        return [
            'image_indexes_to_skip' => ['nullable', 'array'],
            'image_indexes_to_skip.*' => ['integer', 'min:0'],
            'image_separator' => ['nullable', 'string', 'max:10'],
            'active' => ['nullable', 'boolean'],
            'download_mode' => ['nullable', 'string', 'in:all,new_products_only,products_without_images'],
        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        // Available if mapper config is completed
        return (bool) $pipeline->getMapperConfig();
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Images prepare configuration saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/ImagesPrepareConfigStep';
    }
}
