<?php

namespace App\Factories\ImportPipeline\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\ReaderConfigStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

class ReaderConfigStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new ReaderConfigStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->config()->updateOrCreate(
            ['type' => ImportPipelineStep::ReaderConfig->value],
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
            'reader_type' => ['required', 'string', 'in:csv,json,xml'],

            // Options validation
            'options' => ['required', 'array'],

            // CSV options
            'options.delimiter' => ['required_if:reader_type,csv', 'nullable', 'string', 'max:1'],
            'options.enclosure' => ['nullable', 'string', 'max:1'],
            'options.escape' => ['nullable', 'string', 'max:1'],
            'options.has_header' => ['nullable', 'boolean'],
            'options.trim' => ['nullable', 'boolean'],

            // JSON options
            'options.entry_point' => ['nullable', 'string', 'max:255'],

            // XML options
            'options.keep_root' => ['nullable', 'boolean'],
            'options.entry_point' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        // Available if downloader config is completed
        return (bool) $pipeline->getDownloaderConfig();
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Reader configuration saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/ReaderConfigStep';
    }
}
