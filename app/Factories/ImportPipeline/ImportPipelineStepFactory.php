<?php

namespace App\Factories\ImportPipeline;

use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\ImportDashboard\ImportDashboardService;
use Spatie\ViewModels\ViewModel;

class ImportPipelineStepFactory
{
    /**
     * Steps configuration in order.
     */
    private array $steps = [
        ImportPipelineStep::BasicInfo,
        ImportPipelineStep::DownloaderConfig,
        ImportPipelineStep::ReaderConfig,
        ImportPipelineStep::FilterConfig,
        ImportPipelineStep::MapperConfig,
        ImportPipelineStep::ImagesPrepareConfig,
        ImportPipelineStep::Preview,
    ];

    /** @var array<string, ImportPipelineStepInterface> */
    private array $stepImplementors = [];

    /**
     * Constructor.
     */
    public function __construct(private readonly ImportDashboardService $importDashboardService)
    {
        $this->stepImplementors = [
            ImportPipelineStep::BasicInfo->value => new Steps\BasicInfoStep,
            ImportPipelineStep::DownloaderConfig->value => new Steps\DownloaderConfigStep,
            ImportPipelineStep::ReaderConfig->value => new Steps\ReaderConfigStep,
            ImportPipelineStep::FilterConfig->value => new Steps\FilterConfigStep,
            ImportPipelineStep::MapperConfig->value => new Steps\MapperConfigStep,
            ImportPipelineStep::ImagesPrepareConfig->value => new Steps\ImagesPrepareConfigStep,
            ImportPipelineStep::Preview->value => new Steps\PreviewStep,
        ];

        // Inject ImportDashboardService into each step
        foreach ($this->stepImplementors as $step) {
            $step->importDashboardService = $this->importDashboardService;
        }
    }

    /**
     * Get the step implementor.
     */
    public function getStepImplementor(ImportPipelineStep $step): ImportPipelineStepInterface
    {
        return $this->stepImplementors[$step->value];
    }

    /**
     * Create the appropriate ViewModel for the given step.
     */
    public function createViewModel(ImportPipeline $pipeline, ImportPipelineStep $step): ViewModel
    {
        return $this->getStepImplementor($step)->getViewModel($pipeline);
    }

    /**
     * Process the data for the current step.
     */
    public function processStep(ImportPipeline $pipeline, ImportPipelineStep $step, array $data): ImportPipeline
    {
        return $this->getStepImplementor($step)->process($pipeline, $data);
    }

    /**
     * Get validation rules for a step.
     */
    public function getValidationRules(ImportPipelineStep $step): array
    {
        return $this->getStepImplementor($step)->getValidationRules();
    }

    /**
     * Check if a step is available for a pipeline.
     */
    public function isStepAvailable(ImportPipeline $pipeline, ImportPipelineStep $step): bool
    {
        return $this->getStepImplementor($step)->isAvailable($pipeline);
    }

    /**
     * Get the view path for a step.
     */
    public function getViewPath(ImportPipelineStep $step): string
    {
        return $this->getStepImplementor($step)->getViewPath();
    }

    /**
     * Get the success message for a step.
     */
    public function getSuccessMessage(ImportPipelineStep $step): string
    {
        return $this->getStepImplementor($step)->getSuccessMessage();
    }

    /**
     * Get the error message for a step.
     */
    public function getErrorMessage(ImportPipelineStep $step): string
    {
        return $this->getStepImplementor($step)->getErrorMessage();
    }

    /**
     * Get the next step after the current one.
     */
    public function getNextStep(ImportPipelineStep $currentStep): ?ImportPipelineStep
    {
        $currentIndex = array_search($currentStep, $this->steps);

        if ($currentIndex === false || $currentIndex >= count($this->steps) - 1) {
            return null;
        }

        return $this->steps[$currentIndex + 1];
    }

    /**
     * Get the previous step before the current one.
     */
    public function getPreviousStep(ImportPipelineStep $currentStep): ?ImportPipelineStep
    {
        $currentIndex = array_search($currentStep, $this->steps);

        if ($currentIndex === false || $currentIndex <= 0) {
            return null;
        }

        return $this->steps[$currentIndex - 1];
    }

    /**
     * Get the route for the next step.
     */
    public function getNextStepRoute(ImportPipeline $pipeline, ImportPipelineStep $currentStep): ?string
    {
        $nextStep = $this->getNextStep($currentStep);

        if (! $nextStep) {
            return null;
        }

        return route('dashboard.import.pipelines.step.show', [
            'pipeline' => $pipeline->id,
            'step' => $nextStep->value,
        ]);
    }

    /**
     * Get the route for the previous step.
     */
    public function getPreviousStepRoute(ImportPipeline $pipeline, ImportPipelineStep $currentStep): ?string
    {
        $previousStep = $this->getPreviousStep($currentStep);

        if (! $previousStep) {
            return null;
        }

        return route('dashboard.import.pipelines.step.show', [
            'pipeline' => $pipeline->id,
            'step' => $previousStep->value,
        ]);
    }

    /**
     * Get all steps in order.
     */
    public function getAllSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get the first step.
     */
    public function getFirstStep(): ImportPipelineStep
    {
        return $this->steps[0];
    }
}
