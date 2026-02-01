<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\PipelineStage;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\TestDataMapperViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\DataMapper\DTO\MappingRuleData;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Psr\Log\LoggerInterface;
use Spatie\ViewModels\ViewModel;

final class TestDataMapperController extends AbstractTestController
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
        return ImportPipelineStep::MapperConfig;
    }

    /**
     * Get the test type name for logging.
     */
    protected function getTestType(): string
    {
        return 'Data mapper';
    }

    /**
     * Create view model from test result.
     */
    protected function createViewModelFromResult(array $result): ViewModel
    {
        return TestDataMapperViewModel::fromMapperResult($result);
    }

    /**
     * Perform the actual data mapper test logic.
     */
    protected function performTest(ImportPipeline $pipeline): array
    {
        $this->logger->info('Starting data mapper test', [
            'pipeline_id' => $pipeline->id,
        ]);

        try {

            $config = \Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig::fromModel($pipeline, false);
            $result = $this->importPipelineService->executeToStage($config, PipelineStage::MAP);

            $sampleMappedData = array_slice($result->mappingResult->mappedData, 0, 5);
            $sampleRawData = array_slice($result->readResult->data, 0, 3);

            return [
                'success' => empty($result->mappingResult->errors),
                'message' => empty($result->mappingResult->errors)
                    ? sprintf('Data mapper test completed successfully! Mapped %d records.', count($result->mappingResult->mappedData))
                    : sprintf('Data mapper test completed with %d error(s). Mapped %d records.', count($result->mappingResult->errors), count($result->mappingResult->mappedData)),
                'details' => [
                    'downloader_type' => $config->downloadRequest->options['type'] ?? '',
                    'reader_type' => $config->readerConfig->type,
                    'input_rows' => count($result->readResult->data),
                    'mapped_rows' => count($result->mappingResult->mappedData),
                    'errors_count' => count($result->mappingResult->errors),
                    'mapping_rules_count' => count($config->mappingConfig->mappingRules),
                    'data_size' => strlen($result->downloadResult->fileSize),
                    'sample_raw_data' => $sampleRawData,
                    'sample_mapped_data' => $sampleMappedData,
                    'errors' => $result->mappingResult->errors,
                    'mapping_stats' => [
                        'total_fields' => count($config->mappingConfig->mappingRules),
                        'required_fields' => count(array_filter($config->mappingConfig->mappingRules->toArray(), fn ($rule) => $rule['isRequired'])),
                        'default_values_used' => count(array_filter($config->mappingConfig->mappingRules->toArray(), fn ($rule) => $rule['defaultValue'] !== null)),
                    ],
                ],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Data mapper test failed: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                    'mappings_count' => count($config->mappingConfig->mappingRules),
                ],
            ];
        }
    }

    /**
     * Convert field mappings array to MappingRuleData objects.
     *
     * @param  array<int, array<string, mixed>>  $fieldMappings
     * @return array<int, MappingRuleData>
     */
    private function convertToMappingRules(array $fieldMappings): array
    {
        $mappingRules = [];

        foreach ($fieldMappings as $mapping) {
            $mappingRules[] = new MappingRuleData(
                sourceField: $mapping['source_field'] ?? '',
                targetField: $mapping['target_field'] ?? '',
                transformation: $mapping['transformation'] ?? 'none',
                isRequired: $mapping['required'] ?? false,
                defaultValue: $mapping['default_value'] ?? null,
                valueMapping: array_column($mapping['value_mapping'] ?? [], 'to', 'from')
            );
        }

        return $mappingRules;
    }
}
