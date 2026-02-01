<?php

declare(strict_types=1);

namespace App\Factories\ImportPipeline\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\MapperConfigStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\DataMapper\ValueTransformer;
use Spatie\ViewModels\ViewModel;

final class MapperConfigStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new MapperConfigStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->config()->updateOrCreate(
            ['type' => ImportPipelineStep::MapperConfig->value],
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
            'field_mappings' => ['required', 'array', 'min:1'],
            'field_mappings.*.source_field' => ['required', 'string'],
            'field_mappings.*.target_field' => ['required', 'string'],
            'field_mappings.*.transformation' => ['nullable', 'string', 'in:'.implode(',', array_keys(resolve(ValueTransformer::class)->getTransformerOptions()))],
            'field_mappings.*.transformation_params' => ['nullable', 'array'],
            // Value mapping (from => to pairs)
            'field_mappings.*.value_mapping' => ['nullable', 'array'],
            'field_mappings.*.value_mapping.*.from' => ['required_with:field_mappings.*.value_mapping', 'string'],
            'field_mappings.*.value_mapping.*.to' => ['required_with:field_mappings.*.value_mapping', 'string'],
            'field_mappings.*.required' => ['boolean'],
            'field_mappings.*.default_value' => ['nullable', 'string'],
            'field_mappings.*.validation_rules' => ['nullable', 'array'],
        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        // Available if filter config is completed
        return (bool) $pipeline->getFilterConfig();
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Mapper configuration saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/MapperConfigStep';
    }
}
