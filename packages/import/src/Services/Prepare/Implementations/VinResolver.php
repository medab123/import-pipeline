<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Implementations;

use Elaitech\Import\Services\Generator\VinGenerator;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * VIN Resolver
 *
 * Generates VIN from stock_id when VIN is missing.
 */
final readonly class VinResolver implements ResolverInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Generate VIN from stock_id.
     *
     * Generates a 17-character VIN using "VINAD" prefix and stock_id padded with zeros.
     *
     * @param  array<string, mixed>  $row  The row data
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return array<string, mixed> The row with generated VIN
     */
    public function resolve(array $row, PrepareConfigurationData $config): array
    {
        if (! empty($row['vin']) || empty($row['stock_id'])) {
            return $row;
        }

        $stockId = (string) $row['stock_id'];

        try {
            $vin = VinGenerator::generate($stockId);
            $row['vin'] = $vin;

            $this->logger->debug('VIN generated from stock_id', [
                'stock_id' => $stockId,
                'vin' => $vin,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Failed to generate VIN from stock_id', [
                'stock_id' => $stockId,
                'error' => $e->getMessage(),
            ]);
        }

        return $row;
    }
}
