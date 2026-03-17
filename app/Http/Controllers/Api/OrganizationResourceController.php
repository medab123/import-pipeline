<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportPipelineResult;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'result_data'  => $result?->data,
                'created_at'   => $result?->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Abort with 404 if the route-bound pipeline is not the one the token belongs to.
     */
    private function abortUnlessOwned(ImportPipeline $pipeline): void
    {
        if ($pipeline->id !== app('auth_pipeline')->id) {
            abort(404, 'Pipeline not found.');
        }
    }
}
