<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\ImportMapping;
use App\Models\Dealer;
use App\Models\Organization;
use App\Models\OrganizationToken;
use App\Models\Scrap;
use App\Services\FbmpTokenService;
use App\Services\GoogleSheetsService;
use App\Services\Import\CreateScrapPipelineService;
use Elaitech\Import\Enums\ImportPipelineStep;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Elaitech\Import\Services\Jobs\ProcessImportPipelineJob;
use Elaitech\Import\Services\Pipeline\Services\FeedKeysService;
use Elaitech\Import\Services\Pipeline\Services\TargetFieldsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SyncDealersFromSheet extends Command
{
    protected $signature = 'dealers:sync-from-sheet
        {--dry-run : Show what would be created without making changes}
        {--org-uuid= : Organization UUID to assign new dealers to}';

    protected $description = 'Sync dealers from Google Sheet — creates new dealers, scrap sources, pipelines, generates AI mappings, and FBMP tokens. Rolls back all DB changes on failure.';

    public function handle(
        GoogleSheetsService $sheetsService,
        CreateScrapPipelineService $pipelineService,
        FbmpTokenService $fbmpTokenService,
        FeedKeysService $feedKeysService,
        TargetFieldsService $targetFieldsService,
    ): int {
        $orgUuid = $this->option('org-uuid') ?: config('scrap.google_sheets.default_org_uuid');

        $organization = Organization::where('uuid', $orgUuid)->first();

        if (! $organization) {
            $this->error("Organization not found: {$orgUuid}");

            return self::FAILURE;
        }

        // Set org context for services that depend on it
        app()->instance('organization', $organization);

        $this->info('Reading Google Sheet...');

        try {
            $rows = $sheetsService->readDealerSheet();
        } catch (\Exception $e) {
            $this->error("Failed to read Google Sheet: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Found '.count($rows).' rows in sheet.');

        $newCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($rows as $index => $row) {
            $sheetRowNumber = $index + 2; // +2 because header is row 1 and index is 0-based

            // Skip rows that already have a key or pipeline ID (already processed)
            if ($row['key'] || $row['pipeline_id']) {
                $skippedCount++;

                continue;
            }

            // Only process rows where all 3 required fields are filled
            if (empty($row['website']) || empty($row['provider']) || empty($row['file_name'])) {
                $this->warn("Row {$sheetRowNumber}: Waiting for website, provider, and file name to be filled.");

                continue;
            }

            $dealerName = $this->extractDealerName($row['website']);

            if ($this->option('dry-run')) {
                $this->line("  [DRY RUN] Would create: {$dealerName} | {$row['provider']} | {$row['file_name']}");
                $newCount++;

                continue;
            }

            $this->info("Row {$sheetRowNumber}: Processing dealer '{$dealerName}'...");

            try {
                $pipeline = $this->processRow(
                    $orgUuid,
                    $dealerName,
                    $row,
                    $pipelineService,
                    $fbmpTokenService,
                    $feedKeysService,
                    $targetFieldsService,
                    $sheetsService,
                    $sheetRowNumber,
                );

                // All DB changes committed — now write success to sheet
                $orgToken = OrganizationToken::first();
                $sheetsService->updateRow($sheetRowNumber, $orgToken->token, $pipeline->id);
                $sheetsService->updateRowSuccess($sheetRowNumber);

                $this->info("  ✓ Dealer created, Pipeline #{$pipeline->id}, Mappings generated.");
                $newCount++;

            } catch (\Throwable $e) {
                // DB was rolled back by processRow — write error to sheet
                $errorCount++;
                $this->error("  ✗ Row {$sheetRowNumber} failed: {$e->getMessage()}");

                Log::error('SyncDealersFromSheet: row failed, all changes rolled back.', [
                    'row' => $sheetRowNumber,
                    'dealer_name' => $dealerName,
                    'error' => $e->getMessage(),
                    'trace' => Str::limit($e->getTraceAsString(), 500),
                ]);

                try {
                    $sheetsService->updateRowError($sheetRowNumber, $e->getMessage());
                } catch (\Throwable $sheetError) {
                    $this->error("  ✗ Could not update sheet with error: {$sheetError->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("Done. New: {$newCount}, Skipped: {$skippedCount}, Errors: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single row inside a DB transaction.
     * Creates dealer, scrap, pipeline, generates AI mappings, and dispatches processing.
     * If anything fails, the transaction is rolled back and the exception is re-thrown.
     */
    private function processRow(
        string $orgUuid,
        string $dealerName,
        array $row,
        CreateScrapPipelineService $pipelineService,
        FbmpTokenService $fbmpTokenService,
        FeedKeysService $feedKeysService,
        TargetFieldsService $targetFieldsService,
        GoogleSheetsService $sheetsService,
        int $sheetRowNumber,
    ): ImportPipeline {
        return DB::transaction(function () use (
            $orgUuid, $dealerName, $row,
            $pipelineService, $fbmpTokenService,
            $feedKeysService, $targetFieldsService,
            $sheetRowNumber,
        ) {
            // 1. Create dealer
            $dealer = Dealer::create([
                'organization_uuid' => $orgUuid,
                'name' => $dealerName,
                'status' => 'pending',
                'website_urls' => [$row['website']],
                'payment_period' => 'month',
            ]);

            $this->info("    Created Dealer #{$dealer->id}");

            // 2. Create scrap source
            $scrap = Scrap::create([
                'organization_uuid' => $orgUuid,
                'dealer_id' => $dealer->id,
                'ftp_file_path' => $row['file_name'],
                'provider' => $row['provider'],
            ]);

            $this->info("    Created Scrap #{$scrap->id}");

            // 3. Create pipeline with configs
            $pipeline = $pipelineService->createForScrap($scrap);
            $this->info("    Created Pipeline #{$pipeline->id}");

            // 4. Generate AI mappings synchronously (instead of dispatching to queue)
            $this->info('    Generating AI mappings...');
            $this->generateMappingsSync($pipeline, $feedKeysService, $targetFieldsService);
            $this->info('    AI mappings generated successfully.');

            // 5. Generate FBMP token
            $userEmail = Str::slug($dealerName, '_').'@gmail.com';
            $fbmpTokenService->generateAndSave($dealer, $userEmail);
            $this->info('    FBMP token generated.');

            // 6. Dispatch pipeline processing (queued — this is safe because
            //    the pipeline now has valid mappings committed to DB)
            ProcessImportPipelineJob::dispatch($pipeline);
            $this->info('    Pipeline processing dispatched.');

            Log::info('SyncDealersFromSheet: dealer fully created.', [
                'dealer_id' => $dealer->id,
                'dealer_name' => $dealerName,
                'pipeline_id' => $pipeline->id,
                'sheet_row' => $sheetRowNumber,
            ]);

            return $pipeline;
        });
    }

    /**
     * Generate AI field mappings synchronously.
     * This is the same logic as GenerateScrapPipelineMappingsJob but runs inline.
     *
     * @throws \RuntimeException if source fields or target fields are empty, or AI fails
     */
    private function generateMappingsSync(
        ImportPipeline $pipeline,
        FeedKeysService $feedKeysService,
        TargetFieldsService $targetFieldsService,
    ): void {
        // 1. Pull source fields by running download → read
        $sourceFields = $feedKeysService->getFeedKeys($pipeline);

        if (empty($sourceFields)) {
            throw new \RuntimeException(
                "No source fields found for pipeline #{$pipeline->id}. Check FTP file/reader config."
            );
        }

        // 2. Pull organization target fields
        $targetFields = $targetFieldsService->getTargetFields();

        if (empty($targetFields)) {
            throw new \RuntimeException(
                'No target fields configured for this organization. Add target fields first.'
            );
        }

        // 3. Generate mappings via AI agent
        $agent = ImportMapping::make();
        $agent->setSourceFields($sourceFields);
        $agent->setTargetFields($targetFields);

        $response = $agent->prompt('');

        /** @var array{field_mappings: array<int, array<string, mixed>>, message: string} $result */
        $result = method_exists($response, 'toArray') ? $response->toArray() : (array) $response;
        $fieldMappings = $result['field_mappings'] ?? [];

        if (empty($fieldMappings)) {
            throw new \RuntimeException(
                "AI mapping returned no field mappings for pipeline #{$pipeline->id}."
            );
        }

        // 4. Persist mappings into the mapper-config
        $existing = ImportPipelineConfig::where('pipeline_id', $pipeline->id)
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
                'pipeline_id' => $pipeline->id,
                'organization_uuid' => $pipeline->organization_uuid,
                'type' => ImportPipelineStep::MapperConfig->value,
                'config_data' => json_encode(['field_mappings' => $fieldMappings]),
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        }

        Log::info('SyncDealersFromSheet: AI mappings generated.', [
            'pipeline_id' => $pipeline->id,
            'mappings_count' => count($fieldMappings),
        ]);
    }

    /**
     * Extract a clean dealer name from the website URL.
     */
    private function extractDealerName(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;

        // Remove www. and TLD
        $name = preg_replace('/^www\./', '', $host);
        $name = preg_replace('/\.(com|net|org|io|co|us|ca|uk)$/', '', $name);

        return $name;
    }
}
