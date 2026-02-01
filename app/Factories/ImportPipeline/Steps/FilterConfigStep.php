<?php

declare(strict_types=1);

namespace App\Factories\ImportPipeline\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\FilterConfigStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

final class FilterConfigStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new FilterConfigStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->config()->updateOrCreate(
            ['type' => ImportPipelineStep::FilterConfig->value],
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
            // Flat rules (no groups)
            'rules' => ['nullable', 'array'],
            'rules.*.key' => ['required', 'string', 'max:255'],
            'rules.*.operator' => ['required', 'string', 'in:equals,not_equals,contains,not_contains,starts_with,ends_with,regex,not_regex,greater_than,less_than,greater_than_or_equal,less_than_or_equal,in,not_in,is_null,is_not_null,is_empty,is_not_empty,between,not_between'],
            'rules.*.value' => ['nullable'], // Allow mixed types (string for most, array for in/not_in)
            'rules.*.description' => ['nullable', 'string', 'max:500'],
            'rules.*.case_sensitive' => ['nullable', 'boolean'],
            'rules.*.regex_flags' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        // Available if reader config is completed
        return (bool) $pipeline->getReaderConfig();
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Filter configuration saved successfully!';
    }

    /**
     * Get the view path for the step.
     */
    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/FilterConfigStep';
    }
}
