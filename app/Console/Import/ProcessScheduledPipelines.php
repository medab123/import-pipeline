<?php

declare(strict_types=1);

namespace App\Console\Import;

use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Services\Jobs\ProcessImportPipelineJob;
use Elaitech\Import\Services\Pipeline\Contracts\PipelineSchedulingServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ProcessScheduledPipelines extends Command
{
    protected $signature = 'import:pipelines:process-scheduled
                            {--frequency= : Process pipelines with specific frequency (daily, weekly, monthly)}
                            {--dry-run : Show what would be processed without actually processing}';

    protected $description = 'Process scheduled import pipelines based on their frequency and timing';

    public function __construct(
        private readonly PipelineSchedulingServiceInterface $schedulingService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $frequency = $this->option('frequency');
        $dryRun = $this->option('dry-run');

        $this->info('Starting scheduled pipeline processing...');

        $pipelines = $this->schedulingService->getScheduledPipelines(
            $frequency ? ImportPipelineFrequency::from($frequency) : null
        );

        if ($pipelines->isEmpty()) {
            $this->info('No pipelines to process at this time.');

            return self::SUCCESS;
        }

        $this->info("Found {$pipelines->count()} pipeline(s) to process");

        if ($dryRun) {
            $this->showDryRunResults($pipelines);

            return self::SUCCESS;
        }

        $processedCount = 0;
        $skippedCount = 0;

        foreach ($pipelines as $pipeline) {
            try {
                if ($this->schedulingService->isReadyForExecution($pipeline)) {
                    ProcessImportPipelineJob::dispatch($pipeline);
                    $processedCount++;

                    $this->info("Dispatched pipeline: {$pipeline->name} (ID: {$pipeline->id})");

                    Log::info('Scheduled pipeline dispatched', [
                        'pipeline_id' => $pipeline->id,
                        'company_id' => $pipeline->company_id,
                        'frequency' => $pipeline->frequency->value,
                    ]);
                } else {
                    $skippedCount++;
                    $this->warn("Skipped pipeline: {$pipeline->name} (ID: {$pipeline->id}) - not ready for execution");
                }
            } catch (\Exception $e) {
                $this->error("Failed to dispatch pipeline {$pipeline->name}: ".$e->getMessage());

                Log::error('Failed to dispatch scheduled pipeline', [
                    'pipeline_id' => $pipeline->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processing complete: {$processedCount} dispatched, {$skippedCount} skipped");

        return self::SUCCESS;
    }

    private function showDryRunResults(\Illuminate\Database\Eloquent\Collection $pipelines): void
    {
        $this->info('DRY RUN - Pipelines that would be processed:');

        $headers = ['ID', 'Name', 'Company', 'Frequency', 'Start Time', 'Status'];
        $rows = [];

        foreach ($pipelines as $pipeline) {
            $rows[] = [
                $pipeline->id,
                $pipeline->name,
                $pipeline->company->name ?? 'N/A',
                $pipeline->frequency->getLabel(),
                $pipeline->start_time?->format('H:i') ?? 'N/A',
                $this->schedulingService->isReadyForExecution($pipeline) ? 'Ready' : 'Not Ready',
            ];
        }

        $this->table($headers, $rows);
    }
}
