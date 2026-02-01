<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Factories;

use App\Enums\ProductStatus;
use Elaitech\Import\Services\Pipeline\Contracts\PrepareServiceInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareResultData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Data Prepare Service
 *
 * Prepares and transforms data before saving, including:
 * - Category ID resolution
 * - VIN/Stock ID generation
 * - Data normalization
 */
final readonly class PrepareFactory implements PrepareServiceInterface
{
    public function __construct(
        private ResolverFactoryInterface $resolverFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * Prepare data by applying configured transformations.
     */
    public function prepare(PrepareConfigurationData $config): PrepareResultData
    {
        $originalData = $config->data;
        $preparedData = [];
        $errors = [];
        $transformationStats = [
            'category_resolved' => 0,
            'stock_id_generated' => 0,
            'vin_generated' => 0,
            'skipped' => 0,
        ];

        foreach ($originalData as $index => $row) {
            try {
                $preparedRow = $this->transformRow($row, $config);
                $preparedData[] = $preparedRow;
            } catch (\Throwable $e) {
                $errors[(string) $index] = "Row {$index}: {$e->getMessage()}";
                $transformationStats['skipped']++;

                $this->logger->warning('Row preparation failed', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ]);
            }
        }

        return new PrepareResultData(
            preparedData: $preparedData,
            originalData: $originalData,
            totalRows: count($originalData),
            preparedRows: count($preparedData),
            skippedRows: count($errors),
            transformationStats: $transformationStats,
            errors: $errors,
        );
    }

    /**
     * Transform a single row of data.
     *
     * @param  array<string, mixed>  $row  The row data
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return array<string, mixed> The transformed row
     */
    private function transformRow(array $row, PrepareConfigurationData $config): array
    {
        $preparedRow = $row;
        $preparedRow['status'] = ProductStatus::PUBLISHED->value;

        foreach ($this->resolverFactory->getAvailableTypes() as $transformationName) {
            if (! $this->resolverFactory->supports($transformationName)) {
                $this->logger->warning('Unsupported transformation', [
                    'transformation' => $transformationName,
                ]);

                continue;
            }

            try {
                $resolver = $this->resolverFactory->for($transformationName);
                $preparedRow = $resolver->resolve($preparedRow, $config);
            } catch (\Throwable $e) {
                $this->logger->error('Transformation failed', [
                    'transformation' => $transformationName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $preparedRow;
    }

    /**
     * Update transformation statistics based on changes made.
     *
     * @param  string  $transformationName  The transformation name
     * @param  array<string, mixed>  $beforeRow  The row before transformation
     * @param  array<string, mixed>  $afterRow  The row after transformation
     * @param  array<string, int>  $stats  Reference to transformation statistics
     */
    private function updateStats(
        string $transformationName,
        array $beforeRow,
        array $afterRow,
        array &$stats
    ): void {
        match ($transformationName) {
            'category' => $this->updateCategoryStats($beforeRow, $afterRow, $stats),
            'generate_stock_id_from_vin' => $this->updateStockIdStats($beforeRow, $afterRow, $stats),
            'generate_vin_from_stock_id' => $this->updateVinStats($beforeRow, $afterRow, $stats),
            default => null,
        };
    }

    /**
     * Update category resolution statistics.
     *
     * @param  array<string, mixed>  $beforeRow  The row before transformation
     * @param  array<string, mixed>  $afterRow  The row after transformation
     * @param  array<string, int>  $stats  Reference to transformation statistics
     */
    private function updateCategoryStats(array $beforeRow, array $afterRow, array &$stats): void
    {
        $beforeCategoryId = $beforeRow['category_id'] ?? null;
        $afterCategoryId = $afterRow['category_id'] ?? null;

        if ($beforeCategoryId !== $afterCategoryId && isset($afterCategoryId)) {
            $stats['category_resolved']++;
        }
    }

    /**
     * Update stock ID generation statistics.
     *
     * @param  array<string, mixed>  $beforeRow  The row before transformation
     * @param  array<string, mixed>  $afterRow  The row after transformation
     * @param  array<string, int>  $stats  Reference to transformation statistics
     */
    private function updateStockIdStats(array $beforeRow, array $afterRow, array &$stats): void
    {
        $beforeStockId = $beforeRow['stock_id'] ?? null;
        $afterStockId = $afterRow['stock_id'] ?? null;

        if (empty($beforeStockId) && ! empty($afterStockId)) {
            $stats['stock_id_generated']++;
        }
    }

    /**
     * Update VIN generation statistics.
     *
     * @param  array<string, mixed>  $beforeRow  The row before transformation
     * @param  array<string, mixed>  $afterRow  The row after transformation
     * @param  array<string, int>  $stats  Reference to transformation statistics
     */
    private function updateVinStats(array $beforeRow, array $afterRow, array &$stats): void
    {
        $beforeVin = $beforeRow['vin'] ?? null;
        $afterVin = $afterRow['vin'] ?? null;

        if (empty($beforeVin) && ! empty($afterVin)) {
            $stats['vin_generated']++;
        }
    }
}
