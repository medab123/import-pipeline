<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Agents\ImportMapping;
use App\Models\Organization;
use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Elaitech\Import\Services\Jobs\ProcessImportPipelineJob;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Elaitech\Import\Services\Pipeline\Services\TargetFieldsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class GenerateScrapPipelineMappingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum number of attempts before the job is marked as failed. */
    public int $tries = 1;

    /** Allow up to 3 minutes for AI + FTP operations. */
    public int $timeout = 180;

    public function __construct(private readonly ImportPipeline $pipeline) {}

    public function handle(FeedKeysService $feedKeysService, TargetFieldsService $targetFieldsService): void
    {
        // Restore organization context so services that depend on it work correctly
        $organization = Organization::where('uuid', $this->pipeline->organization_uuid)->first();

        if ($organization) {
            app()->instance('organization', $organization);
        }

        // 1. Pull source fields by running the pipeline through download → read
        $sourceFields = $feedKeysService->getFeedKeys($this->pipeline);

        if (empty($sourceFields)) {
            Log::warning('GenerateScrapPipelineMappingsJob: no source fields found, skipping AI mapping.', [
                'pipeline_id' => $this->pipeline->id,
            ]);

            return;
        }

        // 2. Pull organization target fields
        $targetFields = $targetFieldsService->getTargetFields();

        if (empty($targetFields)) {
            Log::warning('GenerateScrapPipelineMappingsJob: no target fields available, skipping AI mapping.', [
                'pipeline_id' => $this->pipeline->id,
            ]);

            return;
        }

        // 3. Generate field mappings via the AI agent
        $agent = ImportMapping::make();
        $agent->setSourceFields($sourceFields);
        $agent->setTargetFields($targetFields);

        $response = $agent->prompt('');
        /** @var array{field_mappings: array<int, array<string, mixed>>, message: string} $result */
        $result = method_exists($response, 'toArray') ? $response->toArray() : (array) $response;
        $fieldMappings = $result['field_mappings'] ?? [];

        // 4. Persist the mappings into the mapper-config (created as empty placeholder earlier).
        //    We use DB::table() to avoid touching vendor model fillable.
        $existing = ImportPipelineConfig::where('pipeline_id', $this->pipeline->id)
            ->where('type', ImportPipelineStep::MapperConfig->value)
            ->value('id');

        if ($existing) {
            DB::table('import_pipeline_configs')
                ->where('id', $existing)
                ->update([
                    'config_data' => json_encode(['field_mappings' => $fieldMappings]),
                    'updated_at' => now()->toDateTimeString(),
                ]);
        } else {
            DB::table('import_pipeline_configs')->insert([
                'pipeline_id' => $this->pipeline->id,
                'organization_uuid' => $this->pipeline->organization_uuid,
                'type' => ImportPipelineStep::MapperConfig->value,
                'config_data' => json_encode(['field_mappings' => $fieldMappings]),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }

        ProcessImportPipelineJob::dispatch($this->pipeline);

        Log::info('GenerateScrapPipelineMappingsJob: mappings generated successfully.', [
            'pipeline_id' => $this->pipeline->id,
            'mappings_count' => count($fieldMappings),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateScrapPipelineMappingsJob failed.', [
            'pipeline_id' => $this->pipeline->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
