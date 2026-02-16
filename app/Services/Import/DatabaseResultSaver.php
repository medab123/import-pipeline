<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ImportPipelineResult;
use Elaitech\Import\Services\Core\Contracts\ResultSaverInterface;
use Elaitech\Import\Services\Pipeline\DTOs\PipelinePassable;
use Elaitech\Import\Services\Pipeline\DTOs\SaveResultData;
use Elaitech\Import\Models\ImportPipeline;

class DatabaseResultSaver implements ResultSaverInterface
{
    public function save(PipelinePassable $passable, string|int $targetId): SaveResultData
    {
        $pipelineId = $passable->config->pipelineId;

        $pipeline = ImportPipeline::find($pipelineId);

        if (!$pipeline) {
             throw new \RuntimeException("Pipeline not found: {$pipelineId}");
        }

        $execution = $pipeline->latestRunningExecution();

        if ($execution) {
             ImportPipelineResult::create([
                 'organization_uuid' => $pipeline->organization_uuid ?? \Illuminate\Support\Str::uuid()->toString(), // Fallback if not present
                 'pipeline_id' => $pipelineId,
                 'execution_id' => $execution->id,
                 'data' => $passable->prepareResult->preparedData,
            ]);
        }

        return new SaveResultData(
            totalProcessed: $passable->readResult ? $passable->readResult->totalRows : 0
        );
    }
}
