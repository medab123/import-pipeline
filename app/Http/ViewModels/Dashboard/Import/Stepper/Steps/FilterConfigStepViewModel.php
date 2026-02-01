<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Filter\Contracts\FilterInterface;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class FilterConfigStepViewModel extends ViewModel
{
    private Collection $filterConfig;

    public function __construct(
        private readonly ImportPipeline $pipeline
    ) {
        $this->filterConfig = $this->getFilterConfig();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::FilterConfig,
        ]);
    }

    /**
     * Get current configuration values.
     */
    public function config(): Collection
    {
        return $this->filterConfig;
    }

    /**
     * Get rules (flat) from configuration.
     */
    public function rules(): array
    {
        return $this->filterConfig->get('rules', []);
    }

    /**
     * Get available filter operators.
     *
     * @return array<string, string>
     */
    public function availableOperators(): array
    {
        return resolve(FilterInterface::class)->getAvailableOperators();
    }

    /**
     * Get test result from session.
     *
     * @return array<string, mixed>|null
     */
    public function testResult(): ?array
    {
        return session('testResult');
    }

    /**
     * Get available feed field keys from the pipeline's reader step.
     * These are the fields available in the read file that can be used for filtering.
     */
    public function feedKeys(): array
    {
        return app(FeedKeysService::class)->getFeedKeys($this->pipeline);
    }

    /**
     * Get the filter configuration from the pipeline.
     */
    private function getFilterConfig(): Collection
    {
        $config = $this->pipeline->getFilterConfig();

        if (! $config) {
            return collect([]);
        }

        return collect($config->config_data ?? []);
    }
}
