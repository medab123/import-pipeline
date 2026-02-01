<?php

namespace App\Factories\ImportPipeline\Steps;

use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\BasicInfoStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

class BasicInfoStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new BasicInfoStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->update([
            'name' => $data['name'] ?? $pipeline->name,
            'description' => $data['description'] ?? $pipeline->description,
            'target_id' => $data['target_id'] ?? $pipeline->target_id,
            'frequency' => $data['frequency'] ?? $pipeline->frequency,
            'is_active' => $data['auto_start'] ?? $pipeline->is_active,
            'start_time' => $data['start_time'],
            'updated_by' => auth()->id(),
        ]);

        return $pipeline->fresh();
    }

    /**
     * Get the validation rules for the step.
     */
    public function getValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_id' => ['required', 'integer'],
            'frequency' => ['required', 'string', 'in:once,daily,weekly,monthly'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        return true;
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Basic information saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/BasicInfoStep';
    }
}
