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
     * List all pipelines for the authenticated organization.
     * Only returns pipelines that the token has access to.
     */
    public function pipelines(Request $request): JsonResponse
    {
        $organization = app('organization');
        $organizationToken = app('organization_token');

        $query = ImportPipeline::where('organization_uuid', $organization->uuid);

        // If token has specific pipelines assigned, filter by them
        if ($organizationToken->pipelines()->count() > 0) {
            $allowedPipelineIds = $organizationToken->pipelines()->pluck('pipeline_id')->toArray();
            $query->whereIn('id', $allowedPipelineIds);
        }

        $pipelines = $query->with(['executions' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $pipelines->items(),
            'meta' => [
                'current_page' => $pipelines->currentPage(),
                'last_page' => $pipelines->lastPage(),
                'per_page' => $pipelines->perPage(),
                'total' => $pipelines->total(),
            ],
        ]);
    }

    /**
     * Get a specific pipeline by ID.
     */
    public function pipeline(ImportPipeline $pipeline): JsonResponse
    {
        $organization = app('organization');
        $organizationToken = app('organization_token');

        if ($pipeline->organization_uuid !== $organization->uuid) {
            return response()->json([
                'message' => 'Pipeline not found.',
            ], 404);
        }

        // Check if token has access to this pipeline
        if (! $organizationToken->canAccessPipeline($pipeline)) {
            return response()->json([
                'message' => 'Access denied. Token does not have permission to access this pipeline.',
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
                'description' => $pipeline->description,
                'is_active' => $pipeline->is_active,
                'last_executed_at' => $pipeline->last_executed_at?->toIso8601String(),
                'next_execution_at' => $pipeline->next_execution_at?->toIso8601String(),
                'created_at' => $pipeline->created_at->toIso8601String(),
                'updated_at' => $pipeline->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get executions for a specific pipeline.
     */
    public function executions(ImportPipeline $pipeline, Request $request): JsonResponse
    {
        $organization = app('organization');
        $organizationToken = app('organization_token');

        if ($pipeline->organization_uuid !== $organization->uuid) {
            return response()->json([
                'message' => 'Pipeline not found.',
            ], 404);
        }

        // Check if token has access to this pipeline
        if (! $organizationToken->canAccessPipeline($pipeline)) {
            return response()->json([
                'message' => 'Access denied. Token does not have permission to access this pipeline.',
            ], 403);
        }

        $executions = ImportPipelineExecution::where('pipeline_id', $pipeline->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $executions->map(function ($execution) {
                return [
                    'id' => $execution->id,
                    'status' => $execution->status->value,
                    'started_at' => $execution->started_at?->toIso8601String(),
                    'completed_at' => $execution->completed_at?->toIso8601String(),
                    'total_rows' => $execution->total_rows,
                    'processed_rows' => $execution->processed_rows,
                    'success_rate' => $execution->success_rate,
                    'processing_time' => $execution->processing_time,
                    'memory_usage' => $execution->memory_usage,
                    'error_message' => $execution->error_message,
                    'created_at' => $execution->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $executions->currentPage(),
                'last_page' => $executions->lastPage(),
                'per_page' => $executions->perPage(),
                'total' => $executions->total(),
            ],
        ]);
    }

    /**
     * Get a specific execution by ID.
     */
    public function execution(ImportPipeline $pipeline, int $execution): JsonResponse
    {
        $organization = app('organization');
        $organizationToken = app('organization_token');

        if ($pipeline->organization_uuid !== $organization->uuid) {
            return response()->json([
                'message' => 'Pipeline not found.',
            ], 404);
        }

        // Check if token has access to this pipeline
        if (! $organizationToken->canAccessPipeline($pipeline)) {
            return response()->json([
                'message' => 'Access denied. Token does not have permission to access this pipeline.',
            ], 403);
        }

        $executionModel = ImportPipelineExecution::where('id', $execution)
            ->where('pipeline_id', $pipeline->id)
            ->first();

        if (! $executionModel) {
            return response()->json([
                'message' => 'Execution not found.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $executionModel->id,
                'status' => $executionModel->status->value,
                'started_at' => $executionModel->started_at?->toIso8601String(),
                'completed_at' => $executionModel->completed_at?->toIso8601String(),
                'total_rows' => $executionModel->total_rows,
                'processed_rows' => $executionModel->processed_rows,
                'success_rate' => $executionModel->success_rate,
                'processing_time' => $executionModel->processing_time,
                'memory_usage' => $executionModel->memory_usage,
                'error_message' => $executionModel->error_message,
                'result_data' => $executionModel->result_data,
                'created_at' => $executionModel->created_at->toIso8601String(),
                'updated_at' => $executionModel->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get results for a specific execution.
     */
    public function executionResults(ImportPipeline $pipeline): JsonResponse
    {
        $organization = app('organization');
        $organizationToken = app('organization_token');

        if ($pipeline->organization_uuid !== $organization->uuid) {
            return response()->json([
                'message' => 'Pipeline not found.',
            ], 404);
        }

        // Check if token has access to this pipeline
        if (! $organizationToken->canAccessPipeline($pipeline)) {
            return response()->json([
                'message' => 'Access denied. Token does not have permission to access this pipeline.',
            ], 403);
        }

        $result = ImportPipelineResult::where('pipeline_id', $pipeline->id)
            ->where('organization_uuid', $organization->uuid)
            ->latest('created_at')
            ->first();

        return response()->json([
            'data' => [
                'execution_id' => $result->execution_id,
                'result_data' => $result ? $result->data : null,
                'created_at' => $result?->created_at?->toIso8601String(),
            ],
        ]);
    }
}
