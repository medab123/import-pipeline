<?php

namespace App\Factories\ImportPipeline;

use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Models\ImportPipeline;

abstract class AbstractImportPipelineStep implements ImportPipelineStepInterface
{
    /**
     * The import dashboard service.
     */
    public ImportDashboardServiceInterface $importDashboardService {
        set(ImportDashboardServiceInterface $value) {
            $this->importDashboardService = $value;
        }
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        return true;
    }

    /**
     * Get the error message for when the step is not available.
     */
    public function getErrorMessage(): string
    {
        return 'This step is not available for the current pipeline.';
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Step completed successfully.';
    }
}
