<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ImagesPrepareConfigStepViewModel extends ViewModel
{
    private Collection $imagesPrepareConfig;

    public function __construct(
        private readonly ImportPipeline $pipeline,
    ) {
        $this->imagesPrepareConfig = $this->getImagesPrepareConfig();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::ImagesPrepareConfig,
        ]);
    }

    public function config(): Collection
    {
        return $this->imagesPrepareConfig;
    }

    public function imageIndexesToSkip(): array
    {
        return (array) $this->imagesPrepareConfig->get('image_indexes_to_skip', []);
    }

    public function imageSeparator(): string
    {
        return $this->imagesPrepareConfig->get('image_separator', ',');
    }

    public function active(): bool
    {
        return $this->imagesPrepareConfig->get('active', false);
    }

    public function downloadMode(): string
    {
        return $this->imagesPrepareConfig->get('download_mode', 'all');
    }

    private function getImagesPrepareConfig(): Collection
    {
        $config = $this->pipeline->getImagesPrepareConfig();
        if (! $config) {
            return collect([
                'image_indexes_to_skip' => [],
                'image_separator' => ',',
                'active' => false,
                'download_mode' => 'all',
            ]);
        }

        return collect($config->config_data ?? []);
    }
}
