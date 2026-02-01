<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\ToastNotificationVariant;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\Controllers\Controller;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Psr\Log\LoggerInterface;
use Spatie\ViewModels\ViewModel;

/**
 * Abstract base controller for pipeline test controllers.
 * Provides common functionality for validation, error handling, and responses.
 */
abstract class AbstractTestController extends Controller
{
    public function __construct(
        protected readonly ImportPipelineStepFactory $stepFactory,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Handle the test request.
     */
    public function __invoke(ImportPipeline $pipeline, Request $request): RedirectResponse
    {
        try {
            $step = $this->getPipelineStep();
            $rules = $this->stepFactory->getValidationRules($step);

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $this->toast('Validation failed: '.$validator->messages()->first(), ToastNotificationVariant::Destructive);

                return back()->withErrors($validator->errors());
            }

            $this->stepFactory->processStep($pipeline, $step, $request->all());

            $testResult = $this->performTest($pipeline);
            $testViewModel = $this->createViewModelFromResult($testResult);

            $this->showToastNotification($testViewModel);

            return $this->redirectWithTestResult($pipeline, $testViewModel);

        } catch (\Exception $e) {
            $this->logger->error($this->getTestType().' test failed', [
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
     * Perform the actual test logic. Must be implemented by child classes.
     */
    abstract protected function performTest(ImportPipeline $pipeline): array;

    /**
     * Get the pipeline step for this test.
     */
    abstract protected function getPipelineStep(): ImportPipelineStep;

    /**
     * Get the test type name for logging.
     */
    abstract protected function getTestType(): string;

    /**
     * Create view model from test result.
     */
    abstract protected function createViewModelFromResult(array $result): ViewModel;

    /**
     * Show appropriate toast notification based on test result.
     */
    protected function showToastNotification(ViewModel $viewModel): void
    {
        $isSuccess = $this->callViewModelMethod($viewModel, 'isSuccess', false);
        $variant = $isSuccess
            ? ToastNotificationVariant::Default
            : ToastNotificationVariant::Destructive;

        $message = $this->callViewModelMethod($viewModel, 'getMessage', 'Test completed');

        $this->toast($message, $variant);
    }

    /**
     * Redirect to step with test result.
     */
    protected function redirectWithTestResult(ImportPipeline $pipeline, ViewModel $testViewModel): RedirectResponse
    {
        $testResult = $this->callViewModelMethod($testViewModel, 'testResult', []);

        return redirect()->route('dashboard.import.pipelines.step.show', [
            'pipeline' => $pipeline->id,
            'step' => $this->getPipelineStep()->value,
        ])->with('testResult', $testResult);
    }

    /**
     * Call a method on view model if it exists, otherwise return default.
     */
    private function callViewModelMethod(ViewModel $viewModel, string $method, mixed $default): mixed
    {
        return method_exists($viewModel, $method)
            ? $viewModel->{$method}()
            : $default;
    }

    /**
     * Create error view model from exception.
     */
    protected function createErrorViewModel(\Exception $e): ViewModel
    {
        $result = [
            'success' => false,
            'message' => 'Test failed: '.$e->getMessage(),
            'details' => [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ],
        ];

        return $this->createViewModelFromResult($result);
    }
}
