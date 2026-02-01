<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Implementations;

use App\Models\Category;
use Elaitech\Import\Services\Pipeline\DTOs\PrepareConfigurationData;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Category Resolver
 *
 * Resolves category_id from category name, slug, or identifier.
 */
final readonly class CategoryResolver implements ResolverInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Resolve category ID from category name, slug, or identifier.
     *
     * @param  array<string, mixed>  $row  The row data
     * @param  PrepareConfigurationData  $config  The preparation configuration
     * @return array<string, mixed> The row with resolved category_id
     */
    public function resolve(array $row, PrepareConfigurationData $config): array
    {
        if (isset($row['category_id']) && is_numeric($row['category_id']) && $row['category_id'] > 0) {
            return $row;
        }

        // Get resolver config from config file
        $resolverConfig = config('import-pipelines.resolvers.category.config', []);
        $categoryField = $resolverConfig['field'] ?? 'category';
        $matchBy = $resolverConfig['match_by'] ?? 'slug';

        if (empty($row[$categoryField])) {
            $defaultCategory = Category::first();
            $row['category_id'] = $defaultCategory?->id ?? 1;

            $this->logger->warning('Category field empty, using default', [
                'category_field' => $categoryField,
                'default_category_id' => $row['category_id'],
            ]);

            return $row;
        }

        $categoryValue = $row[$categoryField];

        $category = match ($matchBy) {
            'slug' => Category::where('slug', Str::slug($categoryValue))->where('is_active', true)->first(),
            'name' => Category::where('name', $categoryValue)->where('is_active', true)->first(),
            'id' => Category::where('id', (int) $categoryValue)->where('is_active', true)->first(),
            default => null,
        };

        if ($category) {
            $row['category_id'] = $category->id;

            $this->logger->debug('Category resolved', [
                'category_value' => $categoryValue,
                'category_id' => $category->id,
                'match_by' => $matchBy,
            ]);
        } else {
            $defaultCategory = Category::first();
            $row['category_id'] = $defaultCategory?->id ?? 1;

            $this->logger->warning('Category not found, using default', [
                'category_value' => $categoryValue,
                'match_by' => $matchBy,
                'default_category_id' => $row['category_id'],
            ]);
        }

        return $row;
    }
}
