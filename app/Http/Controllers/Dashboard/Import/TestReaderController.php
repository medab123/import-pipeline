<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\PipelineStage;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\TestReaderViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\Services\PipelineTestDataService;
use Psr\Log\LoggerInterface;
use Spatie\ViewModels\ViewModel;

final class TestReaderController extends AbstractTestController
{
    public function __construct(
        private readonly ImportPipelineInterface $pipelineService,
        private readonly PipelineTestDataService $testDataService,
        ImportPipelineStepFactory $stepFactory,
        LoggerInterface $logger,
    ) {
        parent::__construct($stepFactory, $logger);
    }

    /**
     * Get the pipeline step for this test.
     */
    protected function getPipelineStep(): ImportPipelineStep
    {
        return ImportPipelineStep::ReaderConfig;
    }

    /**
     * Get the test type name for logging.
     */
    protected function getTestType(): string
    {
        return 'Reader';
    }

    /**
     * Create view model from test result.
     */
    protected function createViewModelFromResult(array $result): ViewModel
    {
        return TestReaderViewModel::fromReaderResult($result);
    }

    /**
     * Perform the actual reader test logic.
     */
    protected function performTest(ImportPipeline $pipeline): array
    {
        $pipelineConfig = ImportPipelineConfig::fromModel($pipeline, requireMapper: false);

        $this->logger->info('Starting reader test', [
            'pipeline_id' => $pipeline->id,
            'options' => $pipelineConfig->downloadRequest->options,
        ]);

        try {
            $result = $this->pipelineService->executeToStage($pipelineConfig, PipelineStage::READ);

            return [
                'success' => true,
                'message' => 'Reader test completed successfully!',
                'details' => [
                    'options_used' => $pipelineConfig->readerConfig->toArray(),
                    'data_size' => $result->downloadResult?->fileSize ?? 'unknown',
                    'records_processed' => $result->readResult?->totalRows ?? 'unknown',
                    'first_few_records' => array_slice($result->readResult?->data ?? [], 0, 3),
                ],
            ];

        } catch (ReaderException $e) {
            return [
                'success' => false,
                'message' => 'Reader test failed: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error during reader test: '.$e->getMessage(),
                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }
}
