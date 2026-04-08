<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Scrap;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateScrapPipelineService
{
    /**
     * Create a fully pre-configured import pipeline for the given scrap source.
     *
     * Steps created:
     *   1. downloader-config     – FTP downloader pointing at the scrap file
     *   2. reader-config         – CSV reader with sensible defaults
     *   3. filter-config         – empty rules (no filtering by default)
     *   4. images-prepare-config – default image handling
     *   5. mapper-config         – empty; filled in later by GenerateScrapPipelineMappingsJob
     */
    public function createForScrap(Scrap $scrap): ImportPipeline
    {
        $ftpConfig = config('scrap.ftp');

        // Use the bare filename as the pipeline name
        $pipelineName = pathinfo($scrap->ftp_file_path, PATHINFO_BASENAME);

        // forceFill bypasses the vendor model's $fillable so token and
        // organization_uuid (both absent from vendor fillable) are persisted.
        $pipeline = (new ImportPipeline)->forceFill([
            'name' => $pipelineName,
            'target_id' => $scrap->dealer_id,
            'frequency' => ImportPipelineFrequency::DAILY,
            'start_time' => now()->format('H:i'),
            'is_active' => true,
            'token' => 'org_'.Str::random(40),
            'organization_uuid' => $scrap->organization_uuid,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        $pipeline->save();

        $this->createConfigs($pipeline, $scrap, $ftpConfig);

        return $pipeline->fresh();
    }

    private function createConfigs(ImportPipeline $pipeline, Scrap $scrap, array $ftpConfig): void
    {
        $now = now()->toDateTimeString();
        $orgUuid = $scrap->organization_uuid;

        // Use DB::table() so we can set organization_uuid without touching vendor fillable.
        // The configs are bulk-inserted here; model events are not fired — that is intentional
        // as the pipeline itself is freshly created and we do not need the activity-log hooks.
        DB::table('import_pipeline_configs')->insert([

            // 1. Downloader – FTP with credentials from config/scrap.php
            [
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $orgUuid,
                'type' => ImportPipelineStep::DownloaderConfig->value,
                'config_data' => json_encode([
                    'downloader_type' => 'ftp',
                    'options' => [
                        'source' => '',
                        'host' => $ftpConfig['host'],
                        'port' => (int) $ftpConfig['port'],
                        'username' => $ftpConfig['username'],
                        'password' => $ftpConfig['password'],
                        'file' => $scrap->ftp_file_path,
                        'timeout' => 30,
                        'retry_attempts' => 3,
                        'method' => 'GET',
                        'headers' => [],
                        'body' => null,
                        'verify_ssl' => true,
                        'follow_redirects' => true,
                    ],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 2. Reader – CSV with sensible defaults
            [
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $orgUuid,
                'type' => ImportPipelineStep::ReaderConfig->value,
                'config_data' => json_encode([
                    'reader_type' => 'csv',
                    'options' => [
                        'delimiter' => ',',
                        'enclosure' => '"',
                        'escape' => '\\',
                        'has_header' => true,
                        'trim' => true,
                        'entry_point' => '//rss/channel/item',
                        'keep_root' => false,
                    ],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 3. Filter – no rules by default
            [
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $orgUuid,
                'type' => ImportPipelineStep::FilterConfig->value,
                'config_data' => json_encode(['rules' => []]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 4. Images prepare – default settings
            [
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $orgUuid,
                'type' => ImportPipelineStep::ImagesPrepareConfig->value,
                'config_data' => json_encode([
                    'image_indexes_to_skip' => [],
                    'image_separator' => ',',
                    'images_key' => 'images',
                    'active' => true,
                    'download_mode' => 'all',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // 5. Mapper – empty placeholder; AI job will populate this
            [
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $orgUuid,
                'type' => ImportPipelineStep::MapperConfig->value,
                'config_data' => json_encode(['field_mappings' => []]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
