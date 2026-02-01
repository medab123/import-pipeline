<?php

namespace App\Http\ViewModels\Dashboard\Import\Stepper;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class CreateStepViewModel extends ViewModel
{
    private array $steps = [
        ImportPipelineStep::BasicInfo,
        ImportPipelineStep::DownloaderConfig,
        ImportPipelineStep::ReaderConfig,
        ImportPipelineStep::FilterConfig,
        ImportPipelineStep::MapperConfig,
        ImportPipelineStep::ImagesPrepareConfig,
        ImportPipelineStep::Preview,
    ];

    public function __construct(
        private readonly ImportPipeline $pipeline,
        private readonly ImportPipelineStep $step,
        private readonly ImportPipelineStepFactory $stepFactory
    ) {}

    /**
     * @return Collection<StepViewModel>
     */
    public function steps(): Collection
    {
        return collect($this->steps)
            ->map(function (ImportPipelineStep $step, int $index) {
                return new StepViewModel(
                    step: $step,
                    route: route($step->route(), ['pipeline' => $this->pipeline->id, 'step' => $step->value]),
                    isAvailable: $this->stepFactory->isStepAvailable($this->pipeline, $step),
                    index: $index + 1,
                    title: $step->title(),
                    description: $step->description(),
                );
            });
    }

    public function current(): StepViewModel
    {
        return $this->steps()->first(fn (StepViewModel $step) => $step->step() === $this->step);
    }
}
