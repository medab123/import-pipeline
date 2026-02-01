<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Product;

use Elaitech\Import\Contracts\Services\Product\ProductActivityLogServiceInterface;
use App\Data\Product\ProductActivityTimelineData;
use App\Models\Product;
use Illuminate\Support\Collection;

use function collect;

final readonly class ProductActivityLogService implements ProductActivityLogServiceInterface
{
    private const int DEFAULT_PER_PAGE = 15;

    public function getTimelineByUuid(string $uuid, int $perPage = self::DEFAULT_PER_PAGE): ProductActivityTimelineData
    {
        $relations = [
            'activities.causer',
        ];

        /** @var Product|null $product */
        $product = Product::query()
            ->where('uuid', $uuid)
            ->with($relations)
            ->first();

        if (! $product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Product with UUID {$uuid} not found");
        }

        $allActivities = collect($product->activities ?? [])
            ->values()
            ->sortByDesc('created_at')
            ->take($perPage);

        return resolve(ProductActivityTimelineData::class, [
            'product' => $product,
            'activities' => $allActivities,
        ]);
    }
}
