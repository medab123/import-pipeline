<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\TargetFieldRole;
use App\Models\ImportPipelineResult;
use App\Models\PipelineInventory;
use App\Models\TargetField;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Core\Contracts\ResultSaverInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;
use Illuminate\Support\Str;

class DatabaseResultSaver implements ResultSaverInterface
{
    public function save(PipelinePassable $passable, string|int $targetId): SaveResultData
    {
        $pipelineId = $passable->config->pipelineId;

        $pipeline = ImportPipeline::find($pipelineId);

        if (! $pipeline) {
            throw new \RuntimeException("Pipeline not found: {$pipelineId}");
        }

        $organizationUuid = $pipeline->organization_uuid;
        $preparedData = $passable->prepareResult->preparedData;

        // Resolve the serial number field to filter out rows without it
        $serialNumberField = $organizationUuid
            ? TargetField::where('organization_uuid', $organizationUuid)
                ->where('role', TargetFieldRole::SerialNumber->value)
                ->first()
            : null;

        // Only keep rows that have a valid serial number
        $stockNumberKey = $serialNumberField?->field;
        if ($stockNumberKey) {
            $preparedData = array_values(array_filter(
                $preparedData,
                function (array $row) use ($stockNumberKey): bool {
                    $value = $row[$stockNumberKey] ?? null;

                    if ($value === null || $value === '') {
                        return false;
                    }

                    $trimmed = trim((string) $value);

                    return $trimmed !== '' && strtolower($trimmed) !== 'null';
                },
            ));
        }

        $execution = $pipeline->latestRunningExecution();

        if ($execution) {
            ImportPipelineResult::create([
                'organization_uuid' => $organizationUuid ?? Str::uuid()->toString(),
                'pipeline_id' => $pipelineId,
                'execution_id' => $execution->id,
                'data' => $preparedData,
            ]);
        }

        $inventoryResult = $this->syncInventory($pipeline, $preparedData, $serialNumberField);

        return new SaveResultData(
            totalProcessed: $passable->readResult ? $passable->readResult->totalRows : 0,
            createdCount: $inventoryResult['created'],
            updatedCount: $inventoryResult['updated'],
            errorCount: $inventoryResult['errorCount'],
            errors: $inventoryResult['errors'],
        );
    }

    /**
     * @return array{created: int, updated: int, skipped: int, deleted: int, errorCount: int, errors: array<string, string>}
     */
    private function syncInventory(ImportPipeline $pipeline, array $preparedData, ?TargetField $serialNumberField): array
    {
        $organizationUuid = $pipeline->organization_uuid;

        if (! $organizationUuid || ! $serialNumberField) {
            return ['created' => 0, 'updated' => 0, 'skipped' => count($preparedData), 'deleted' => 0, 'errorCount' => 0, 'errors' => []];
        }

        $imagesField = TargetField::where('organization_uuid', $organizationUuid)
            ->where('role', TargetFieldRole::Images->value)
            ->first();

        $stockNumberKey = $serialNumberField->field;
        $imagesKey = $imagesField?->field;

        // Preload existing inventory records for this pipeline to avoid N+1
        $existingInventory = PipelineInventory::where('pipeline_id', $pipeline->id)
            ->get()
            ->keyBy('stock_number');

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Track which stock numbers are present in the current import
        $importedStockNumbers = [];

        foreach ($preparedData as $index => $row) {
            try {
                $stockNumber = (string) $row[$stockNumberKey];
                $importedStockNumbers[] = $stockNumber;

                $existing = $existingInventory->get($stockNumber);

                if ($existing) {
                    if ($this->hasDataChanged($existing->product_data, $row, $imagesKey)) {
                        $existing->update(['product_data' => $row]);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    PipelineInventory::create([
                        'organization_uuid' => $organizationUuid,
                        'pipeline_id' => $pipeline->id,
                        'stock_number' => $stockNumber,
                        'product_data' => $row,
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[(string) $index] = $e->getMessage();
            }
        }

        // Delete products that no longer exist in the imported data
        $deleted = 0;
        $staleStockNumbers = $existingInventory->keys()->diff($importedStockNumbers);

        if ($staleStockNumbers->isNotEmpty()) {
            $deleted = PipelineInventory::where('pipeline_id', $pipeline->id)
                ->whereIn('stock_number', $staleStockNumbers->all())
                ->delete();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'deleted' => $deleted,
            'errorCount' => count($errors),
            'errors' => $errors,
        ];
    }

    private function hasDataChanged(array $existingData, array $newData, ?string $imagesKey): bool
    {
        $filteredExisting = $existingData;
        $filteredNew = $newData;

        if ($imagesKey !== null) {
            unset($filteredExisting[$imagesKey], $filteredNew[$imagesKey]);
        }

        ksort($filteredExisting);
        ksort($filteredNew);

        return $filteredExisting !== $filteredNew;
    }
}
