<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportPipelineResult;
use App\Models\PipelineInventory;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Http\JsonResponse;

final class OrganizationResourceController extends Controller
{
    /**
     * Get the latest import result for the token's pipeline.
     */
    public function executionResults(ImportPipeline $pipeline): JsonResponse
    {
        $this->abortUnlessOwned($pipeline);

        $result = ImportPipelineResult::where('pipeline_id', $pipeline->id)
            ->latest('created_at')
            ->first();

        return response()->json([
            'data' => [
                'execution_id' => $result?->execution_id,
                'result_data' => $result?->data,
                'created_at' => $result?->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get paginated inventory products for a pipeline.
     */
    public function products(ImportPipeline $pipeline): JsonResponse
    {
        $this->abortUnlessOwned($pipeline);

        $products = PipelineInventory::where('pipeline_id', $pipeline->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(
                perPage: request()->integer('per_page', 50)
            );

        return response()->json([
            'data' => $products->through(fn (PipelineInventory $item) => [
                'uuid' => $item->uuid,
                'stock_number' => $item->stock_number,
                'product_data' => $item->product_data,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Abort with 404 if the pipeline does not belong to the authenticated organization.
     */
    private function abortUnlessOwned(ImportPipeline $pipeline): void
    {
        $organization = app('organization');

        if ($pipeline->organization_uuid !== $organization->uuid) {
            abort(404, 'Pipeline not found.');
        }
    }
}
