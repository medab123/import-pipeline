<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Implementations;

use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * VIN Resolver
 *
 * Generates VIN from stock_id when VIN is missing.
 */
final readonly class TitleResolver implements ResolverInterface
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
        if (! empty($row['title'])) {
            return $row;
        }

        if (! empty($row['year']) && ! empty($row['make'] && ! empty($row['model']))) {
            $row['title'] = $row['year'].' '.$row['make'].' '.$row['model'];
            $this->logger->debug('Title generated for product', $row);
        }

        return $row;
    }
}
