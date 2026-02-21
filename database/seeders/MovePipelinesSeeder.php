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
            $pipelines = ImportPipeline::where('organization_uuid', '!=', $defaultOrganization->uuid)
                ->whereNotNull('organization_uuid')
                ->get();
            $processedOrganizations = [];
            foreach ($pipelines as $pipeline) {
                $pipelineOrganization = Organization::where('uuid', $pipeline->organization_uuid)->first();
                if (!$pipelineOrganization) {
                    $this->command->warn("Pipeline {$pipeline->id} has invalid organization_uuid: {$pipeline->organization_uuid}");
                    $pipeline->update(['organization_uuid' => $defaultOrganization->uuid]);
                    continue;
                }
                if (in_array($pipelineOrganization->uuid, $processedOrganizations)) {
                    $pipeline->update(['organization_uuid' => $defaultOrganization->uuid]);
                    continue;
                }
                $organizationTokens = OrganizationToken::where('organization_uuid', $pipelineOrganization->uuid)->get();
                
                // Move pipeline to default organization
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

                foreach ($organizationTokens as $token) {
                    $token->update(['organization_uuid' => $defaultOrganization->uuid]);
                    if (!$token->pipelines()->where('pipeline_id', $pipeline->id)->exists()) {
                        $token->pipelines()->attach($pipeline->id);
                    }
                }
                User::where('organization_uuid', $pipelineOrganization->uuid)
                    ->update(['organization_uuid' => $defaultOrganization->uuid]);
                $processedOrganizations[] = $pipelineOrganization->uuid;
                $pipelineOrganization->forceDelete();
            }

            DB::commit();
            $this->command->info("Successfully moved {$pipelines->count()} pipelines to default organization.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error: " . $e->getMessage());
            throw $e;
        }
    }
}
