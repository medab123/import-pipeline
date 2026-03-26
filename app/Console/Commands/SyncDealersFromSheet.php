<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateScrapPipelineMappingsJob;
use App\Models\Dealer;
use App\Models\Organization;
use App\Models\OrganizationToken;
use App\Models\Scrap;
use App\Services\FbmpTokenService;
use App\Services\GoogleSheetsService;
use App\Services\Import\CreateScrapPipelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class SyncDealersFromSheet extends Command
{
    protected $signature = 'dealers:sync-from-sheet
        {--dry-run : Show what would be created without making changes}
        {--org-uuid= : Organization UUID to assign new dealers to}';

    protected $description = 'Sync dealers from Google Sheet — creates new dealers, scrap sources, pipelines, and FBMP tokens for rows without a key.';

    public function handle(
        GoogleSheetsService $sheetsService,
        CreateScrapPipelineService $pipelineService,
        FbmpTokenService $fbmpTokenService,
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

            $this->info("Row {$sheetRowNumber}: Creating dealer '{$dealerName}'...");

            try {
                // 1. Create dealer
                $dealer = Dealer::create([
                    'organization_uuid' => $orgUuid,
                    'name' => $dealerName,
                    'status' => 'pending',
                    'website_urls' => [$row['website']],
                    'payment_period' => 'month',
                ]);

                // 2. Create scrap source
                $scrap = Scrap::create([
                    'organization_uuid' => $orgUuid,
                    'dealer_id' => $dealer->id,
                    'ftp_file_path' => $row['file_name'],
                    'provider' => $row['provider'],
                ]);

                // 3. Create pipeline + dispatch AI mapping
                $pipeline = $pipelineService->createForScrap($scrap);
                GenerateScrapPipelineMappingsJob::dispatch($pipeline);

                // 4. Generate FBMP token
                $userEmail = Str::slug($dealerName, '_').'@gmail.com';
                $fbmpTokenService->generateAndSave($dealer, $userEmail);

                // 5. Create org API token for this pipeline
                $orgToken = OrganizationToken::first();

                // 6. Write token + pipeline ID back to the sheet
                $sheetsService->updateRow($sheetRowNumber, $orgToken->token, $pipeline->id);

                $this->info("  ✓ Dealer #{$dealer->id}, Scrap #{$scrap->id}, Pipeline #{$pipeline->id}, Token created.");
                $newCount++;

                Log::info('SyncDealersFromSheet: new dealer created.', [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealerName,
                    'pipeline_id' => $pipeline->id,
                    'sheet_row' => $sheetRowNumber,
                ]);
            } catch (\Exception $e) {
                $this->error("  ✗ Row {$sheetRowNumber} failed: {$e->getMessage()}");
                Log::error('SyncDealersFromSheet: row failed.', [
                    'row' => $sheetRowNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. New: {$newCount}, Skipped (already processed): {$skippedCount}");

        return self::SUCCESS;
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
