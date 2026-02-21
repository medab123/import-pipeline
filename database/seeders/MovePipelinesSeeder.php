<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ImportPipelineResult;
use App\Models\Organization;
use App\Models\OrganizationToken;
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

        if (!$defaultOrganization) {
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

            // Loop through each organization
            foreach ($uniqueOrganizationUuids as $organizationUuid) {
                $organization = Organization::where('uuid', $organizationUuid)->first();

                if (!$organization) {
                    $this->command->warn("Organization with UUID {$organizationUuid} not found. Moving orphaned pipelines to default.");
                    // Move orphaned pipelines to default organization
                    $orphanedPipelines = ImportPipeline::where('organization_uuid', $organizationUuid)->get();
                    foreach ($orphanedPipelines as $pipeline) {
                        $this->movePipelineAndRelatedRecords($pipeline, $defaultOrganization);
                        $movedPipelinesCount++;
                    }
                    continue;
                }

                // Fetch all pipelines belonging to this organization
                $pipelines = ImportPipeline::where('organization_uuid', $organization->uuid)->get();

                if ($pipelines->isEmpty()) {
                    $this->command->info("Organization {$organization->name} has no pipelines. Skipping.");
                    continue;
                }

                $this->command->info("Processing organization: {$organization->name} ({$pipelines->count()} pipelines)");

                // Process all pipelines belonging to this organization
                foreach ($pipelines as $pipeline) {
                    $this->movePipelineAndRelatedRecords($pipeline, $defaultOrganization);
                    $movedPipelinesCount++;
                }

                // Get tokens from the old organization
                $organizationTokens = OrganizationToken::where('organization_uuid', $organization->uuid)->get();

                // Update tokens and attach all pipelines
                foreach ($organizationTokens as $token) {
                    $token->update(['organization_uuid' => $defaultOrganization->uuid]);

                    // Attach all pipelines from this organization to the token
                    foreach ($pipelines as $pipeline) {
                        if (!$token->pipelines()->where('pipeline_id', $pipeline->id)->exists()) {
                            $token->pipelines()->attach($pipeline->id);
                        }
                    }
                }

                // Move users to default organization
                $usersMoved = User::where('organization_uuid', $organization->uuid)
                    ->update(['organization_uuid' => $defaultOrganization->uuid]);

                $this->command->info("  - Moved {$pipelines->count()} pipelines");
                $this->command->info("  - Moved {$organizationTokens->count()} tokens");
                $this->command->info("  - Moved {$usersMoved} users");

                // Delete the old organization (cascade will handle related data)
                $organization->forceDelete();
                $processedOrganizationsCount++;
            }

            DB::commit();
            $this->command->info("Successfully processed {$processedOrganizationsCount} organizations.");
            $this->command->info("Successfully moved {$movedPipelinesCount} pipelines to default organization.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Move a pipeline and all its related records to the default organization.
     */
    private function movePipelineAndRelatedRecords(ImportPipeline $pipeline, Organization $defaultOrganization): void
    {
        // Update pipeline organization_uuid
        $pipeline->update(['organization_uuid' => $defaultOrganization->uuid]);

        // Update all related configs
        ImportPipelineConfig::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);

        // Get all executions for this pipeline
        $executionIds = ImportPipelineExecution::where('pipeline_id', $pipeline->id)
            ->pluck('id')
            ->toArray();

        // Update all related executions
        ImportPipelineExecution::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);

        // Update all related logs (logs are related to executions, not pipelines)
        if (!empty($executionIds)) {
            ImportPipelineLog::whereIn('execution_id', $executionIds)
                ->update(['organization_uuid' => $defaultOrganization->uuid]);
        }

        // Update results (has both pipeline_id and organization_uuid)
        ImportPipelineResult::where('pipeline_id', $pipeline->id)
            ->update(['organization_uuid' => $defaultOrganization->uuid]);
    }
}
