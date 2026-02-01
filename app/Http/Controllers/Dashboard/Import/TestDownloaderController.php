<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\ToastNotificationVariant;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\Controllers\Controller;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\TestDownloaderViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Downloader\Contracts\DownloaderFactoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psr\Log\LoggerInterface;

final class TestDownloaderController extends Controller
{
    public function __construct(
        private readonly ImportPipelineStepFactory $stepFactory,
        private readonly DownloaderFactoryInterface $downloaderFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Test downloader configuration for a pipeline.
     */
    public function __invoke(ImportPipeline $pipeline, Request $request): RedirectResponse
    {
        try {
            $step = ImportPipelineStep::DownloaderConfig;
            $rules = $this->stepFactory->getValidationRules($step);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $this->toast('Validation failed: '.$validator->messages()->first(), ToastNotificationVariant::Destructive);

                return back()->withErrors($validator->errors());
            }

            $this->stepFactory->processStep($pipeline, $step, $request->all());

            $downloadResult = $this->performDownloaderTest($pipeline);
            $testViewModel = TestDownloaderViewModel::fromDownloadResult($downloadResult);

            $this->showToastNotification($testViewModel);

            return $this->redirectWithTestResult($pipeline, $testViewModel);

        } catch (\Exception $e) {
            $this->logger->error('Downloader test failed', [
                'pipeline_id' => $pipeline->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $errorViewModel = $this->createErrorViewModel($e);
            $this->showToastNotification($errorViewModel);

            return $this->redirectWithTestResult($pipeline, $errorViewModel);
        }
    }

    /**
     * Perform the actual downloader test logic.
     */
    private function performDownloaderTest(ImportPipeline $pipeline): DownloadResultData
    {
        $config = $pipeline->getDownloaderConfig()->config_data;
        $downloaderType = $config['downloader_type'] ?? 'https';
        $downloader = $this->downloaderFactory->for($downloaderType);

        // Prepare options with source included
        $options = $config['options'] ?? [];

        $this->logger->info('Starting downloader test', [
            'pipeline_id' => $pipeline->id,
            'downloader_type' => $downloaderType,
            'options' => $options,
        ]);

        return $downloader->download(
            new DownloadRequestData($options['source'] ?? '', $options)
        );
    }

    /**
     * Show appropriate toast notification based on test result.
     */
    private function showToastNotification(TestDownloaderViewModel $viewModel): void
    {
        $variant = $viewModel->isSuccess()
            ? ToastNotificationVariant::Default
            : ToastNotificationVariant::Destructive;

        $this->toast($viewModel->getMessage(), $variant);
    }

    /**
     * Redirect to downloader config step with test result.
     */
    private function redirectWithTestResult(ImportPipeline $pipeline, TestDownloaderViewModel $testViewModel): RedirectResponse
    {
        return redirect()->route('dashboard.import.pipelines.step.show', [
            'pipeline' => $pipeline->id,
            'step' => ImportPipelineStep::DownloaderConfig->value,
        ])->with('testResult', $testViewModel->testResult());
    }

    /**
     * Create error view model from exception.
     */
    private function createErrorViewModel(\Exception $e): TestDownloaderViewModel
    {
        return TestDownloaderViewModel::fromArray([
            'success' => false,
            'message' => 'Test failed: '.$e->getMessage(),
            'details' => [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ],
        ]);
    }
}
