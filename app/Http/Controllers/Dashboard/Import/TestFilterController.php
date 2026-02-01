<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\PipelineStage;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\TestFilterViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Psr\Log\LoggerInterface;
use Spatie\ViewModels\ViewModel;

final class TestFilterController extends AbstractTestController
{
    public function __construct(
        ImportPipelineStepFactory $stepFactory,
        LoggerInterface $logger,
        private readonly ImportPipelineInterface $importPipelineService,
    ) {
        parent::__construct($stepFactory, $logger);
    }

    /**
     * Get the pipeline step for this test.
     */
    protected function getPipelineStep(): ImportPipelineStep
    {
        return ImportPipelineStep::FilterConfig;
    }

    /**
     * Get the test type name for logging.
     */
    protected function getTestType(): string
    {
        return 'Filter';
    }

    /**
     * Create view model from test result.
     */
    protected function createViewModelFromResult(array $result): ViewModel
    {
        return TestFilterViewModel::fromFilterResult($result);
    }

    /**
     * Perform the actual filter test logic.
     */
    protected function performTest(ImportPipeline $pipeline): array
    {
        $filterConfig = $pipeline->getFilterConfig();
        if (! $filterConfig) {
            throw new \Exception('No filter configuration found. Please configure filters first.');
        }

        $config = $filterConfig->config_data;
        $rules = $config['rules'] ?? [];

        $this->logger->info('Starting filter test', [
            'pipeline_id' => $pipeline->id,
            'rules_count' => count($rules),
        ]);

        try {
            $config = \Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig::fromModel($pipeline, false);
            $result = $this->importPipelineService->executeToStage($config, PipelineStage::FILTER);

            return [
                'success' => true,
                'message' => 'Filter test completed successfully!',
                'details' => [
                    'downloader_type' => $config->downloadRequest->options['type'] ?? '',
                    'reader_type' => $config->readerConfig->type,
                    'original_count' => $result->stats->totalRows,
                    'filtered_count' => $result->stats->filteredRows,
                    'rules_applied' => $config->filterConfig->filterRules,
                    'data_size' => $result->downloadResult->fileSize,
                    'sample_original_data' => array_slice($result->readResult->data, 0, 2),
                    'sample_filtered_data' => array_slice($result->filterResult->filteredData, 0, 3),
                    'filter_stats' => $result->filterResult->filterStats,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Filter test failed: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ];
        }
    }

    private function convertRulesToFilterRules(array $rules): array
    {
        $filterRules = [];

        foreach ($rules as $rule) {
            $filterRules[] = new FilterRuleData(
                key: $rule['key'] ?? '',
                operator: $rule['operator'] ?? 'equals',
                value: $rule['value'] ?? '',
                description: $rule['description'] ?? '',
                caseSensitive: $rule['case_sensitive'] ?? false,
                regexFlags: $rule['regex_flags'] ?? ''
            );
        }

        return $filterRules;
    }
}
