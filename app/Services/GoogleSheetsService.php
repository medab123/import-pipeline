<?php

declare(strict_types=1);

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class GoogleSheetsService
{
    private Sheets $sheets;

    public function __construct()
    {
        $client = new Client;
        $client->setAuthConfig(config('scrap.google_sheets.credentials_path'));
        $client->addScope(Sheets::SPREADSHEETS);

        $this->sheets = new Sheets($client);
    }

    /**
     * Read all rows from the configured dealer spreadsheet.
     *
     * @return array<int, array{website: string, provider: string, file_name: string, key: string|null, pipeline_id: string|null}>
     */
    public function readDealerSheet(): array
    {
        $spreadsheetId = config('scrap.google_sheets.spreadsheet_id');
        $range = config('scrap.google_sheets.range', 'Sheet1!A1:H1000');

        $response = $this->sheets->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues();

        if (empty($rows) || count($rows) < 2) {
            return [];
        }

        // Skip header row
        $header = array_shift($rows);

        return array_map(fn (array $row) => [
            'website' => $row[0] ?? '',
            'provider' => $row[1] ?? '',
            'file_name' => $row[2] ?? '',
            'key' => ! empty($row[3] ?? '') ? $row[3] : null,
            'pipeline_id' => ! empty($row[4] ?? '') ? $row[4] : null,
        ], $rows);
    }

    /**
     * Write the token and pipeline ID back to the sheet for a given row.
     * Row number is 1-indexed (row 2 = first data row after header).
     */
    public function updateRow(int $rowNumber, string $token, int $pipelineId): void
    {
        $spreadsheetId = config('scrap.google_sheets.spreadsheet_id');
        $range = "Sheet1!D{$rowNumber}:E{$rowNumber}";

        $body = new Sheets\ValueRange([
            'values' => [[$token, (string) $pipelineId]],
        ]);

        $this->sheets->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        Log::info('Google Sheet row updated.', [
            'row' => $rowNumber,
            'token' => substr($token, 0, 15).'...',
            'pipeline_id' => $pipelineId,
        ]);
    }

    /**
     * Write an error status and message to the sheet for a given row.
     * Writes to columns F (status) and G (error message).
     */
    public function updateRowError(int $rowNumber, string $errorMessage): void
    {
        $spreadsheetId = config('scrap.google_sheets.spreadsheet_id');
        $range = "Sheet1!F{$rowNumber}:G{$rowNumber}";

        $body = new Sheets\ValueRange([
            'values' => [['error', Str::limit($errorMessage, 500)]],
        ]);

        $this->sheets->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        Log::info('Google Sheet row marked as error.', [
            'row' => $rowNumber,
            'error' => Str::limit($errorMessage, 100),
        ]);
    }

    /**
     * Write a success status to the sheet for a given row.
     * Writes to column F (status) and clears column G (error message).
     */
    public function updateRowSuccess(int $rowNumber): void
    {
        $spreadsheetId = config('scrap.google_sheets.spreadsheet_id');
        $range = "Sheet1!F{$rowNumber}:G{$rowNumber}";

        $body = new Sheets\ValueRange([
            'values' => [['success', '']],
        ]);

        $this->sheets->spreadsheets_values->update(
            $spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );
    }
}
