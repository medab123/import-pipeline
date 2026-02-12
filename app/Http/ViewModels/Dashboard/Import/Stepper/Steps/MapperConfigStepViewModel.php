<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\DataMapper\ValueTransformer;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Elaitech\Import\Services\Pipeline\Services\TargetFieldsService;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class MapperConfigStepViewModel extends ViewModel
{
    private Collection $mapperConfig;

    public function __construct(
        private readonly ImportPipeline $pipeline,
    ) {
        $this->mapperConfig = $this->getMapperConfig();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::MapperConfig,
        ]);
    }

    public function config(): Collection
    {
        return $this->mapperConfig;
    }

    public function fieldMappings(): array
    {
        return $this->mapperConfig->get('field_mappings', []);
    }

    public function supportsValueMapping(): bool
    {
        return true;
    }

    public function availableTransformations(): array
    {
        return app(ValueTransformer::class)->getTransformerOptions();
    }

    public function feedKeys(): array
    {
        return app(FeedKeysService::class)->getFeedKeys($this->pipeline);
    }

    public function targetFields(): array
    {
        return app(TargetFieldsService::class)->getTargetFields();
    }

    public function testResult(): ?array
    {
        return session('testResult');
    }

    private function getMapperConfig(): Collection
    {
        $config = $this->pipeline->getMapperConfig();
        if (! $config) {
            return collect([]);
        }

        return collect($config->config_data ?? []);
    }
}
