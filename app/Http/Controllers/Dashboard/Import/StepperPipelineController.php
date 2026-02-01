<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStep;
use App\Enums\ToastNotificationVariant;
use App\Factories\ImportPipeline\ImportPipelineStepFactory;
use App\Http\Controllers\Controller;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\ImportDashboard\ImportDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Response;

final class StepperPipelineController extends Controller
{
    public function __construct(
        private readonly ImportPipelineStepFactory $stepFactory,
        private readonly ImportDashboardService $dashboardService
    ) {}

    /**
     * Start the pipeline creation process.
     * Creates a draft pipeline and redirects to the first step.
     */
    public function create(): RedirectResponse
    {
        try {
            $pipeline = $this->dashboardService->createPipeline([
                'name' => 'New Pipeline',
                'target_id' => 1,
                'frequency' => ImportPipelineFrequency::DAILY->value,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route(
                'dashboard.import.pipelines.step.show', [
                    'pipeline' => $pipeline->id,
                    'step' => $this->stepFactory->getFirstStep()->value,
                ]
            );
        } catch (\Exception $e) {
            report($e);
            $this->toast('Failed to create pipeline: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.index');
        }
    }

    /**
     * Start the pipeline editing process.
     * Redirects to the first step for editing an existing pipeline.
     */
    public function edit(ImportPipeline $pipeline): RedirectResponse
    {
        try {
            return redirect()->route(
                'dashboard.import.pipelines.step.show',
                [
                    'pipeline' => $pipeline->id,
                    'step' => $this->stepFactory->getFirstStep()->value,
                ]
            );
        } catch (\Exception $e) {
            $this->toast('Failed to edit pipeline: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.index');
        }
    }

    /**
     * Show a specific step for a pipeline.
     */
    public function showStep(ImportPipeline $pipeline, ImportPipelineStep $step): Response|RedirectResponse
    {
        // Check if the step is available
        if (! $this->stepFactory->isStepAvailable($pipeline, $step)) {
            $this->toast($this->stepFactory->getErrorMessage($step), ToastNotificationVariant::Destructive);

            // Redirect to the last available step
            $lastAvailableStep = $this->getLastAvailableStep($pipeline);
            if ($lastAvailableStep) {
                return redirect()->route('dashboard.import.pipelines.step.show', [
                    'pipeline' => $pipeline->id,
                    'step' => $lastAvailableStep->value,
                ]);
            }

            return redirect()->route('dashboard.import.pipelines.index');
        }

        // Create the appropriate ViewModel for the step
        $viewModel = $this->stepFactory->createViewModel($pipeline, $step);

        return inertia($this->stepFactory->getViewPath($step), $viewModel);
    }

    /**
     * Store/update a step for a pipeline.
     */
    public function storeStep(Request $request, ImportPipeline $pipeline, ImportPipelineStep $step): RedirectResponse
    {
        // Get validation rules for the step
        $rules = $this->stepFactory->getValidationRules($step);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $this->toast('Validation failed: '.$validator->messages()->first(), ToastNotificationVariant::Destructive);

            return back()->withErrors($validator->errors());
        }

        try {
            // Process the step data
            $this->stepFactory->processStep($pipeline, $step, $request->all());

            $this->toast($this->stepFactory->getSuccessMessage($step));

            // Redirect to next step or completion
            $nextStepRoute = $this->stepFactory->getNextStepRoute($pipeline, $step);

            if ($nextStepRoute) {
                return redirect($nextStepRoute);
            }

            // If this is the last step, redirect to pipeline index or show page
            return redirect()->route('dashboard.import.pipelines.index');

        } catch (\Exception $e) {
            $this->toast('Failed to save step: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return back();
        }
    }

    /**
     * Navigate to the next step.
     */
    public function nextStep(ImportPipeline $pipeline, ImportPipelineStep $step): RedirectResponse
    {
        $nextStepRoute = $this->stepFactory->getNextStepRoute($pipeline, $step);

        if (! $nextStepRoute) {
            $this->toast('No next step available', ToastNotificationVariant::Destructive);

            return back();
        }

        return redirect($nextStepRoute);
    }

    /**
     * Navigate to the previous step.
     */
    public function previousStep(ImportPipeline $pipeline, ImportPipelineStep $step): RedirectResponse
    {
        $previousStepRoute = $this->stepFactory->getPreviousStepRoute($pipeline, $step);

        if (! $previousStepRoute) {
            $this->toast('No previous step available', ToastNotificationVariant::Destructive);

            return back();
        }

        return redirect($previousStepRoute);
    }

    /**
     * Get the last available step for a pipeline.
     */
    private function getLastAvailableStep(ImportPipeline $pipeline): ?ImportPipelineStep
    {
        $steps = $this->stepFactory->getAllSteps();

        return array_find(array_reverse($steps), fn ($step) => $this->stepFactory->isStepAvailable($pipeline, $step));

    }
}
