<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Pipes;

use Elaitech\Import\Contracts\Services\Product\Creation\ProductFactoryInterface;
use Elaitech\Import\Enums\ImageDownloadMode;
use Elaitech\Import\Enums\PipelineStage;
use App\Models\Product;
use Elaitech\Import\Services\Jobs\ImageDownloadJob;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;
use Closure;
use Psr\Log\LoggerInterface;

/**
 * Save Pipe
 *
 * Handles the save stage of the import pipeline.
 * Saves filtered/mapped data to the database using ExtensibleProductFactory.
 */
final readonly class SavePipe
{
    public function __construct(
        private ProductFactoryInterface $productFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle the save stage.
     */
    public function handle(PipelinePassable $passable, Closure $next): PipelinePassable
    {

        if (empty($passable->prepareResult?->preparedData)) {
            $this->logger->warning('Save stage skipped: no data to save');

            return $passable->withError('Save stage requires mapped data');
        }

        $stageStart = microtime(true);
        $this->logger->info('Starting save stage', [
            'rows_to_save' => count($passable->prepareResult->preparedData),
        ]);

        try {
            $saveResult = $this->saveProducts($passable->prepareResult->preparedData, $passable);
            $stageTiming = microtime(true) - $stageStart;
            $this->logger->info('Save stage completed', [
                'total_processed' => $saveResult->totalProcessed,
                'created' => $saveResult->createdCount,
                'updated' => $saveResult->updatedCount,
                'errors' => $saveResult->errorCount,
                'duration' => $stageTiming,
            ]);

            $updatedPassable = $passable
                ->withSaveResult($saveResult)
                ->withCurrentStage(PipelineStage::SAVE)
                ->withStageMemoryUsage(PipelineStage::SAVE->name, memory_get_usage(true))
                ->withStageTiming(PipelineStage::SAVE->name, $stageTiming)
                ->cleanPreviousStage();

            return $next($updatedPassable);
        } catch (\Throwable $e) {
            $this->logger->error('Save stage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $passable->withError("Save failed: {$e->getMessage()} {$e->getTraceAsString()}");
        }
    }

    /**
     * Save products to the database.
     *
     * @param  array<int, array<string, mixed>>  $data  Array of product data
     * @param  PipelinePassable  $passable  The pipeline passable containing config
     * @return SaveResultData The save result
     */
    private function saveProducts(array $data, PipelinePassable $passable): SaveResultData
    {
        $createdProducts = [];
        $updatedProducts = [];
        $errors = [];
        $totalProcessed = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $errorCount = 0;

        $targetId = $passable->config->targetId;

        if (! $targetId) {
            throw new \RuntimeException('Target ID is required for saving products. Ensure the import pipeline has a target_id set.');
        }

        foreach (array_chunk($data, 100) as $chunk) {
            $stockIds = array_column($chunk, 'stock_id');
            $existing = Product::with('productable.categorizable')->where('company_id', $targetId)
                ->withCount([
                    'media as media_count' => fn ($q) => $q->where('collection_name', Product::MEDIA_COLLECTION),
                ])
                ->whereIn('stock_id', $stockIds)
                ->get()
                ->keyBy('stock_id');

            foreach ($chunk as $index => $productData) {

                try {
                    $productData['company_id'] = $targetId;

                    /** @var Product $existingProduct */
                    $existingProduct = $existing[$productData['stock_id']] ?? null;

                    $isNewProduct = $existingProduct === null;
                    $hasExistingImages = $existingProduct && $existingProduct->media_count;

                    $product = $existingProduct
                        ? $this->productFactory->updateProduct($existingProduct, \Arr::except($productData, 'images'))
                        : $this->productFactory->createProduct(\Arr::except($productData, 'images'));

                    $images = $productData['images'] ?? [];

                    // Check if images should be downloaded based on ImagesPrepareConfigurationData
                    if ($images && $this->shouldDownloadImages($passable, $isNewProduct, $hasExistingImages)) {
                        $this->logger->debug('Dispatching image download job', [
                            'product_id' => $product->id,
                            'stock_id' => $product->stock_id,
                            'is_new' => $isNewProduct,
                            'image_count' => count($images),
                            'download_mode' => $passable->config->imagesPrepareConfig?->downloadMode->value ?? 'none',
                        ]);
                        ImageDownloadJob::dispatch($product->id, $images);
                    } elseif ($images) {
                        $this->logger->debug('Skipping image download', [
                            'product_id' => $product->id,
                            'stock_id' => $product->stock_id,
                            'is_new' => $isNewProduct,
                            'has_existing_images' => $hasExistingImages,
                            'download_mode' => $passable->config->imagesPrepareConfig?->downloadMode->value ?? 'none',
                        ]);
                    }

                    $totalProcessed++;
                    $existingProduct ? $updatedCount++ : $createdCount++;
                    $existingProduct
                        ? $updatedProducts[] = $product->uuid
                        : $createdProducts[] = $product->uuid;

                } catch (\Throwable $e) {
                    $errorCount++;
                    $errors[] = "Row $index: {$e->getMessage()}".$e->getTraceAsString();
                    $this->logger->error("Row {$index} failed", [
                        'error' => $e->getMessage(),
                    ]);
                    unset($existingProduct, $product);
                    gc_collect_cycles();
                }
            }
        }

        return new SaveResultData(
            createdProducts: $createdProducts,
            updatedProducts: $updatedProducts,
            errors: $errors,
            totalProcessed: $totalProcessed,
            createdCount: $createdCount,
            updatedCount: $updatedCount,
            errorCount: $errorCount,
        );
    }

    /**
     * Determine if images should be downloaded for a product based on configuration.
     *
     * @param  PipelinePassable  $passable  The pipeline passable containing config
     * @param  bool  $isNewProduct  Whether this is a newly created product
     * @param  bool  $hasExistingImages  Whether the product already has images
     * @return bool True if images should be downloaded
     */
    private function shouldDownloadImages(PipelinePassable $passable, bool $isNewProduct, bool $hasExistingImages): bool
    {
        $imagesPrepareConfig = $passable->config->imagesPrepareConfig;

        // If images prepare config is not set or not active, don't download
        if (! $imagesPrepareConfig || ! $imagesPrepareConfig->active) {
            return false;
        }

        return match ($imagesPrepareConfig->downloadMode) {
            ImageDownloadMode::ALL => true,
            ImageDownloadMode::NEW_PRODUCTS_ONLY => $isNewProduct,
            ImageDownloadMode::PRODUCTS_WITHOUT_IMAGES => ! $hasExistingImages,
        };
    }
}
