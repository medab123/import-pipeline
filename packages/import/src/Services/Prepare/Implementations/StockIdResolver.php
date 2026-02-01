<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Implementations;

use Elaitech\Import\Services\Generator\StockIdGenerator;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * Stock ID Resolver
 *
 * Generates stock_id from VIN when stock_id is missing.
 */
final readonly class StockIdResolver implements ResolverInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Generate stock_id from VIN.
     *
     * @param  array<string, mixed>  $row  The row data
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return array<string, mixed> The row with generated stock_id
     */
    public function resolve(array $row, PrepareConfigurationData $config): array
    {
        if (! empty($row['stock_id']) || empty($row['vin'])) {
            return $row;
        }

        $vin = (string) $row['vin'];

        if (strlen($vin) < 7) {
            $this->logger->warning('VIN too short to generate stock_id', [
                'vin' => $vin,
            ]);

            return $row;
        }

        $stockId = StockIdGenerator::generate($vin);
        $row['stock_id'] = $stockId;

        $this->logger->debug('Stock ID generated from VIN', [
            'vin' => $vin,
            'stock_id' => $stockId,
        ]);

        return $row;
    }
}
