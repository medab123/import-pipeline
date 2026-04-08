<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DealerAndScrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = storage_path('app/relay_clients.csv');

        if (! file_exists($csvPath)) {
            $this->command->error(sprintf('CSV file not found at: %s', $csvPath));

            return;
        }

        $csvFile = fopen($csvPath, 'r');
        if ($csvFile === false) {
            throw new RuntimeException(sprintf('Failed to open CSV file at: %s', $csvPath));
        }

        // Read and discard the header row
        fgetcsv($csvFile, 2000, ',');

        $now = now();

        DB::beginTransaction();

        try {
            // Remove previous data
            DB::table('scraps')->delete();
            DB::table('dealers')->delete();

            while (($data = fgetcsv($csvFile, 2000, ',')) !== false) {
                // Determine Dealer Name (Index 2)
                $dealerName = trim($data[2] ?? '');

                if ($dealerName === '') {
                    continue;
                }

                if (filter_var($dealerName, FILTER_VALIDATE_URL)) {
                    $host = parse_url($dealerName, PHP_URL_HOST);
                    if ($host) {
                        $host = preg_replace('/^www\./', '', $host);
                        $parts = explode('.', $host);
                        if (count($parts) > 1) {
                            array_pop($parts); // Remove the TLD (e.g., .com, .ca)
                        }
                        $name = implode(' ', $parts);
                        $dealerName = Str::title(str_replace('-', ' ', $name));
                    }
                }

                // Use specific organization UUID instead of creating
                $orgUuid = '07301d3f-9b46-43ed-8861-044209d06b54';

                // Determine Status (Index 3) - Enum mapping
                $rawStatus = strtolower(trim($data[3] ?? ''));
                $status = 'active'; // Default

                if ($rawStatus === 'inactive' || $rawStatus === 'closed' || $rawStatus === 'cancelled') {
                    $status = 'inactive';
                }

                // Create Dealer
                $dealerId = DB::table('dealers')->insertGetId([
                    'organization_uuid' => $orgUuid,
                    'name' => $dealerName,
                    'status' => $status,
                    'notes' => trim($data[4] ?? '') ?: null,
                    'posting_address' => trim($data[5] ?? '') ?: null,
                    'website_url' => trim($data[6] ?? '') ?: null,
                    // Store 'fbmp Access Token' (from column 10) into fbmp_app_access_token
                    'fbmp_app_access_token' => trim($data[10] ?? '') ?: null,
                    // Store 'app' (from column 11) into fbmp_app_url
                    'fbmp_app_url' => trim($data[11] ?? '') ?: null,
                    'payment_period' => 'month',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Create Scrap record mapping
                DB::table('scraps')->insert([
                    'organization_uuid' => $orgUuid,
                    'dealer_id' => $dealerId,
                    'ftp_file_path' => 'N/A', // Omit file path from CSV
                    'provider' => 'relay',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();
            $this->command->info('Successfully seeded Dealers and Scraps from CSV.');
        } catch (Throwable $e) {
            DB::rollBack();
            $this->command->error(sprintf('Seeding failed: %s', $e->getMessage()));
        } finally {
            fclose($csvFile);
        }
    }
}
