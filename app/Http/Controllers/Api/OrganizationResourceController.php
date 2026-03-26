<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportPipelineResult;
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
