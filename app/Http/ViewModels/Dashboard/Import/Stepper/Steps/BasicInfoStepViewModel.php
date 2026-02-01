<?php

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class BasicInfoStepViewModel extends ViewModel
{
    public function __construct(private readonly ImportPipeline $pipeline) {}

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function frequencies(): array
    {
        return ImportPipelineFrequency::getOptions();
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::BasicInfo,
        ]);
    }
}
