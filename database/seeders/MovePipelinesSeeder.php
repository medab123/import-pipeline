<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ImportPipelineResult;
use App\Models\Organization;
use App\Models\User;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Elaitech\Import\Models\ImportPipelineExecution;
use Elaitech\Import\Models\ImportPipelineLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MovePipelinesSeeder extends Seeder
{
    public function run(): void
    {
        $defaultOrganization = Organization::where('slug', 'default')->first();

        if (! $defaultOrganization) {
            $this->command->error('Default organization not found!');

            return;
        }

        DB::beginTransaction();

        try {
            // Retrieve unique organization UUIDs from pipelines (excluding default)
            $uniqueOrganizationUuids = ImportPipeline::where('organization_uuid', '!=', $defaultOrganization->uuid)
                ->whereNotNull('organization_uuid')
                ->distinct()
                ->pluck('organization_uuid')
                ->unique()
                ->values();

            $movedPipelinesCount = 0;
            $processedOrganizationsCount = 0;

            foreach ($uniqueOrganizationUuids as $organizationUuid) {
                $organization = Organization::where('uuid', $organizationUuid)->first();

                if (! $organization) {
                    $this->command->warn("Organization with UUID {$organizationUuid} not found. Moving orphaned pipelines to default.");
                    $orphanedPipelines = ImportPipeline::where('organization_uuid', $organizationUuid)->get();
                    foreach ($orphanedPipelines as $pipeline) {
                        $this->movePipelineAndRelatedRecords($pipeline, $defaultOrganization);
                        $movedPipelinesCount++;
                    }

                    continue;
                }

                $pipelines = ImportPipeline::where('organization_uuid', $organization->uuid)->get();

                if ($pipelines->isEmpty()) {
                    $this->command->info("Organization {$organization->name} has no pipelines. Skipping.");

                    continue;
                }

                $this->command->info("Processing organization: {$organization->name} ({$pipelines->count()} pipelines)");

                foreach ($pipelines as $pipeline) {
                    $this->movePipelineAndRelatedRecords($pipeline, $defaultOrganization);
                    $movedPipelinesCount++;
                }

                $usersMoved = User::where('organization_uuid', $organization->uuid)
                    ->update(['organization_uuid' => $defaultOrganization->uuid]);

                $this->command->info("  - Moved {$pipelines->count()} pipelines");
                $this->command->info("  - Moved {$usersMoved} users");

                $organization->forceDelete();
                $processedOrganizationsCount++;
            }

            DB::commit();
            $this->command->info("Successfully processed {$processedOrganizationsCount} organizations.");
            $this->command->info("Successfully moved {$movedPipelinesCount} pipelines to default organization.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Move a pipeline and all its related records to the default organization.
     */
    private function movePipelineAndRelatedRecords(ImportPipeline $pipeline, Organization $defaultOrganization): void
    {
        $pipeline->update(['organization_uuid' => $defaultOrganization->uuid]);

        ImportPipelineConfig::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);

        $executionIds = ImportPipelineExecution::where('pipeline_id', $pipeline->id)
            ->pluck('id')
            ->toArray();

        ImportPipelineExecution::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);

        if (! empty($executionIds)) {
            ImportPipelineLog::whereIn('execution_id', $executionIds)
                ->update(['organization_uuid' => $defaultOrganization->uuid]);
        }

        ImportPipelineResult::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);
    }
}
