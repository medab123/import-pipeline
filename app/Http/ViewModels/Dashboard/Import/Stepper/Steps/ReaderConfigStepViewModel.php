<?php

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class ReaderConfigStepViewModel extends ViewModel
{
    private Collection $readerConfig;

    public function __construct(
        private readonly ImportPipeline $pipeline
    ) {
        $this->readerConfig = $this->getReaderConfig();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::ReaderConfig,
        ]);
    }

    /**
     * Get current reader type.
     */
    public function readerType(): string
    {
        return $this->readerConfig->get('reader_type', 'csv');
    }

    /**
     * Get current configuration values.
     */
    public function config(): Collection
    {
        return $this->readerConfig;
    }

    /**
     * Get CSV delimiter.
     */
    public function delimiter(): string
    {
        return $this->readerConfig->get('options')['delimiter'] ?? ',';
    }

    /**
     * Get CSV enclosure.
     */
    public function enclosure(): string
    {
        return $this->readerConfig->get('options')['enclosure'] ?? '"';
    }

    /**
     * Get CSV escape character.
     */
    public function escape(): string
    {
        return $this->readerConfig->get('options')['escape'] ?? '\\';
    }

    /**
     * Get has header setting.
     */
    public function hasHeader(): bool
    {
        return $this->readerConfig->get('options')['has_header'] ?? true;
    }

    /**
     * Get trim setting.
     */
    public function trim(): bool
    {
        return $this->readerConfig->get('options')['trim'] ?? true;
    }

    /**
     * Get JSON entry point setting.
     */
    public function entryPoint(): string
    {
        return $this->readerConfig->get('options')['entry_point'] ?? '';
    }

    /**
     * Get XML keep root setting.
     */
    public function keepRoot(): bool
    {
        return $this->readerConfig->get('options')['keep_root'] ?? false;
    }

    /**
     * Get test result from session.
     */
    public function testResult(): ?array
    {
        return session('testResult');
    }

    /**
     * Get the reader configuration from the pipeline.
     */
    private function getReaderConfig(): Collection
    {
        $config = $this->pipeline->getReaderConfig();

        if (! $config) {
            return collect([]);
        }

        return collect($config->config_data ?? []);
    }
}
