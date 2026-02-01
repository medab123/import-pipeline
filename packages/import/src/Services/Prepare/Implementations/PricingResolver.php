<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Implementations;

use Elaitech\Import\Contracts\Services\PriceType\PriceTypeServiceInterface;
use App\Enums\PriceValueType;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PricingResolver
 *
 * Extracts pricing fields from imported data and normalizes them
 * into a structured pricing array for further processing.
 */
final readonly class PricingResolver implements ResolverInterface
{
    /**
     * PricingResolver constructor.
     */
    public function __construct(
        private LoggerInterface $logger,
        private PriceTypeServiceInterface $productPriceTypeService,
    ) {}

    /**
     * Resolves pricing data for a given row and configuration.
     *
     * @param  array  $row  The data row to process
     * @param  PrepareConfigurationData  $config  Configuration for data preparation
     * @return array The row with normalized pricing added
     */
    public function resolve(array $row, PrepareConfigurationData $config): array
    {
        $pricing = [];

        $pricingFields = array_map(
            fn ($code) => $code.'_price',
            array_column(config('price-types'), 'code')
        );

        $pricingFields = array_diff($pricingFields, ['asking_price', 'special_price']);

        foreach ($pricingFields as $field) {
            if (! array_key_exists($field, $row)) {
                $this->logger->info("Pricing field '{$field}' is missing in row.", ['row' => $row]);

                continue;
            }

            try {
                $code = substr($field, 0, -6);
                $priceType = $this->productPriceTypeService->getPriceTypeByCode($code);
                $priceValueType = $this->detectPriceValueType($row[$field]);
                $value = $priceValueType === PriceValueType::PRICE->value
                    ? $this->normalizePrice($row[$field])
                    : $row[$field];

                $pricing[] = [
                    'product_id' => null,
                    'price_type_id' => $priceType->id,
                    'value_type' => $priceValueType,
                    'value' => $value,
                ];

                $this->logger->info("Processed pricing field '{$field}'.", [
                    'value_type' => $priceValueType,
                    'value' => $value,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error("Error processing pricing field '{$field}': ".$e->getMessage(), [
                    'row' => $row,
                    'exception' => $e,
                ]);
            }
        }

        $row['pricing'] = $pricing;

        return $row;
    }

    /**
     * Detects the value type of given price.
     *
     * @return string The type of price: no_price, price, or text
     */
    private function detectPriceValueType(mixed $price): string
    {
        if ($price === null || $price === '') {
            return PriceValueType::NO_PRICE->value;
        }

        if (preg_match('/\d/', (string) $price)) {
            return PriceValueType::PRICE->value;
        }

        return PriceValueType::TEXT->value;
    }

    /**
     * Normalizes a price string into a numeric value.
     */
    private function normalizePrice(mixed $price): float|int
    {
        $value = (string) $price;
        $value = preg_replace('/[^0-9.,]/', '', $value);

        if (substr_count($value, ',') === 1 && substr_count($value, '.') === 0) {
            $value = str_replace(',', '.', $value);
        }

        $value = str_replace(',', '', $value);

        return str_contains($value, '.')
            ? (float) $value
            : (int) $value;
    }
}
