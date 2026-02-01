<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Services\Pipeline\Contracts\ImportPipelineInterface;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class PreviewStepViewModel extends ViewModel
{
    private ?array $pipelineResult = null;

    private ?string $error = null;

    public function __construct(
        private readonly ImportPipeline $pipeline,
        private readonly ImportPipelineInterface $pipelineService
    ) {
        $this->executePipeline();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::Preview,
        ]);
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function hasResult(): bool
    {
        return $this->pipelineResult !== null;
    }

    public function result(): ?array
    {
        return $this->pipelineResult;
    }

    public function previewData(): array
    {
        if (! $this->hasResult()) {
            return [];
        }

        return $this->pipelineResult['prepared_data'] ?? [];
    }

    public function columns(): array
    {
        $previewData = $this->previewData();

        if (empty($previewData)) {
            return [];
        }

        // Get all unique keys from the first few rows
        $columns = [];
        foreach (array_slice($previewData, 0, 10) as $row) {
            if (is_array($row)) {
                $columns = array_merge($columns, array_keys($row));
            }
        }

        return array_values(array_unique($columns));
    }

    public function stats(): array
    {
        if (! $this->hasResult()) {
            return [];
        }

        return [
            'total_rows' => $this->pipelineResult['stats']['total_rows'] ?? 0,
            'mapped_rows' => $this->pipelineResult['stats']['mapped_rows'] ?? 0,
            'filtered_rows' => $this->pipelineResult['stats']['filtered_rows'] ?? 0,
            'processing_time' => $this->pipelineResult['stats']['processing_time'] ?? 0,
            'error_count' => $this->pipelineResult['stats']['error_count'] ?? 0,
        ];
    }

    public function errors(): array
    {
        if (! $this->hasResult()) {
            return [];
        }

        return $this->pipelineResult['errors'] ?? [];
    }

    private function executePipeline(): void
    {
        try {
            $config = ImportPipelineConfig::fromModel($this->pipeline);
            $result = $this->pipelineService->process($config);
            $this->pipelineResult = [
                'prepared_data' => $result->prepareResult?->preparedData ?? null,
                'mapped_data' => $result->mappingResult?->mappedData ?? null,
                'filtered_data' => $result->filterResult?->filteredData ?? null,
                'stats' => [
                    'total_rows' => $result->stats->totalRows,
                    'mapped_rows' => $result->stats->mappedRows,
                    'filtered_rows' => $result->stats->filteredRows,
                    'processing_time' => round($result->stats->processingTime, 3),
                    'error_count' => $result->stats->errorCount,
                ],
                'errors' => $result->getAllErrors(),
            ];
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }
}
