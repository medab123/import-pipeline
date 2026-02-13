<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Import;

use Elaitech\Import\Contracts\Services\ImportDashboard\ImportDashboardServiceInterface;
use Elaitech\Import\Enums\ImportPipelineFrequency;
use App\Enums\ToastNotificationVariant;
use App\Http\Controllers\Controller;
use App\Http\ViewModels\Dashboard\Import\ActivityLogViewModel;
use App\Http\ViewModels\Dashboard\Import\ListActivityLogViewModel;
use App\Http\ViewModels\Dashboard\Import\ListPipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Elaitech\Import\Models\ImportPipelineConfig;
use Elaitech\Import\Services\Jobs\ProcessImportPipelineJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Yaml\Yaml;

final class PipelineController extends Controller
{
    public function __construct(
        private readonly ImportDashboardServiceInterface $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $targetId = $request->get('target_id');
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $paginated = $this->dashboardService->paginatePipelines($targetId, $perPage, $search);

        return inertia('Dashboard/Import/Pipelines/Index', new ListPipelineViewModel($paginated));
    }

    public function show(ImportPipeline $pipeline): Response
    {
        return inertia('Dashboard/Import/Pipelines/Show', new PipelineViewModel($pipeline));
    }

    public function toggleStatus(ImportPipeline $pipeline)
    {
        try {
            $result = $this->dashboardService->togglePipelineStatus($pipeline->id);

            if (! $result) {
                $this->toast('Pipeline not found', ToastNotificationVariant::Destructive);

                return back(303);
            }

            $this->toast('Pipeline status updated successfully!');

            return back(303);
        } catch (\Exception $e) {
            $this->toast('Failed to update pipeline status: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return back(303);
        }
    }

    public function processNow(ImportPipeline $pipeline)
    {
        try {
            $pipeline = $this->dashboardService->getPipeline($pipeline->id);

            if (! $pipeline) {
                $this->toast('Pipeline not found', ToastNotificationVariant::Destructive);

                return back();
            }

            if (! $pipeline->is_active) {
                $this->toast('Pipeline is not active', ToastNotificationVariant::Destructive);

                return back();
            }

            // Dispatch the job to process the pipeline
            ProcessImportPipelineJob::dispatch(
                $pipeline,
                'manual'
            );

            $this->toast('Pipeline processing started successfully!');

            return back();
        } catch (\Exception $e) {
            $this->toast('Failed to start pipeline processing: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return back();
        }
    }

    public function destroy(ImportPipeline $pipeline)
    {
        try {
            $result = $this->dashboardService->deletePipeline($pipeline->id);

            if (! $result) {
                $this->toast('Pipeline not found', ToastNotificationVariant::Destructive);

                return back(303);
            }

            $this->toast('Pipeline deleted successfully!');

            return redirect()->route('dashboard.import.pipelines.index', status: 303);
        } catch (\Exception $e) {
            $this->toast('Failed to delete pipeline: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return back(303);

        }
    }

    public function executions(ImportPipeline $pipeline, Request $request): Response
    {
        $perPage = $request->get('per_page', 15);
        $paginated = $this->dashboardService->paginateExecutionsByPipeline($pipeline->id, $perPage);

        $executions = [];
        foreach ($paginated->items() as $execution) {
            $executions[] = [
                'id' => $execution->id,
                'status' => $execution->status->value,
                'startedAt' => $execution->started_at?->toIso8601String(),
                'completedAt' => $execution->completed_at?->toIso8601String(),
                'totalRows' => $execution->total_rows,
                'processedRows' => $execution->processed_rows,
                'successRate' => $execution->success_rate,
                'processingTime' => $execution->processing_time,
                'memoryUsage' => $execution->memory_usage,
                'errorMessage' => $execution->error_message,
                'createdAt' => $execution->created_at->toIso8601String(),
            ];
        }

        return inertia('Dashboard/Import/Pipelines/Executions', [
            'pipeline' => new PipelineViewModel($pipeline),
            'executions' => $executions,
            'paginator' => [
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
                'perPage' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function showExecution(ImportPipeline $pipeline, int $execution): Response|RedirectResponse
    {
        $executionModel = $this->dashboardService->getExecution($execution);

        if (! $executionModel || $executionModel->pipeline_id !== $pipeline->id) {
            $this->toast('Execution not found', ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.executions', ['pipeline' => $pipeline->id]);
        }

        return inertia('Dashboard/Import/Pipelines/ExecutionDetail', [
            'pipeline' => new PipelineViewModel($pipeline),
            'execution' => [
                'id' => $executionModel->id,
                'status' => $executionModel->status->value,
                'startedAt' => $executionModel->started_at?->toIso8601String(),
                'completedAt' => $executionModel->completed_at?->toIso8601String(),
                'totalRows' => $executionModel->total_rows,
                'processedRows' => $executionModel->processed_rows,
                'successRate' => $executionModel->success_rate,
                'processingTime' => $executionModel->processing_time,
                'memoryUsage' => $executionModel->memory_usage,
                'errorMessage' => $executionModel->error_message,
                'resultData' => $executionModel->result_data,
                'createdAt' => $executionModel->created_at->toIso8601String(),
                'updatedAt' => $executionModel->updated_at->toIso8601String(),
                'logs' => $executionModel->logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'level' => $log->log_level,
                        'message' => $log->message,
                        'context' => $log->context,
                        'createdAt' => $log->created_at->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }

    public function showExecutionResults(ImportPipeline $pipeline, int $execution): Response|RedirectResponse
    {
        $executionModel = $this->dashboardService->getExecution($execution);

        if (! $executionModel || $executionModel->pipeline_id !== $pipeline->id) {
            $this->toast('Execution not found', ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.executions', ['pipeline' => $pipeline->id]);
        }

        $result = \Elaitech\Import\Models\ImportPipelineResult::where('execution_id', $execution)->first();

        return inertia('Dashboard/Import/Pipelines/ExecutionResults', [
            'pipeline' => new PipelineViewModel($pipeline),
            'execution' => [
                'id' => $executionModel->id,
                'status' => $executionModel->status->value,
                'startedAt' => $executionModel->started_at?->toIso8601String(),
                'completedAt' => $executionModel->completed_at?->toIso8601String(),
            ],
            'resultData' => $result ? $result->data : [],
        ]);
    }

    /**
     * Export the given pipeline as a YAML configuration file.
     */
    public function export(Request $request, ImportPipeline $pipeline)
    {
        $this->authorize('export', $pipeline);

        $pipeline->loadMissing('config');
        $yamlArray = $pipeline->toArray();
        $yamlContent = Yaml::dump($yamlArray, 4, 2);

        $fileName = sprintf(
            'pipeline-%d-%s.yaml',
            $pipeline->id,
            Str::slug($pipeline->name ?: 'pipeline', '_')
        );

        return response($yamlContent, 200, [
            'Content-Type' => 'text/yaml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * Import a pipeline definition from an exported YAML file.
     */
    public function import(Request $request): RedirectResponse
    {
        // For import, we need to check authorization without a model instance
        // Create a temporary pipeline instance for authorization check
        $tempPipeline = new ImportPipeline;
        $this->authorize('import', $tempPipeline);

        $validated = $request->validate([
            'yaml_file' => ['required', 'file'],
        ]);

        try {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $validated['yaml_file'];
            $yamlContent = $file->get();

            if ($yamlContent === false) {
                throw new \RuntimeException('Unable to read uploaded file.');
            }

            /** @var array<string, mixed> $data */
            $data = Yaml::parse($yamlContent);
        } catch (\Throwable $e) {
            $this->toast('Invalid YAML file: '.$e->getMessage(), ToastNotificationVariant::Destructive);

            return back(303);
        }

        try {
            $targetId = $data['target_id'];

            if (! $targetId) {
                throw new \InvalidArgumentException('target_id is required in YAML or associated with the authenticated user.');
            }

            $frequencyValue = $data['frequency'] ?? ImportPipelineFrequency::ONCE->value;
            $frequency = ImportPipelineFrequency::from($frequencyValue);

            $pipelineData = [
                'target_id' => 1,
                'name' => $data['name'].' (copy)' ?? 'Imported pipeline',
                'description' => $data['description'] ?? null,
                'is_active' => false,
                'start_time' => $data['start_time'] ?? null,
                'frequency' => $frequency,
            ];

            $pipeline = $this->dashboardService->createPipeline($pipelineData);

            $config = $data['config'] ?? [];

            foreach ($config as $stepConfig) {
                ImportPipelineConfig::query()->create([...Arr::except($stepConfig, 'id'), 'pipeline_id' => $pipeline->id]);
            }

            $pipeline = $pipeline->fresh();
        } catch (\Throwable $e) {
            $this->toast('Failed to import pipeline: '.$e->getMessage(), ToastNotificationVariant::Destructive);
            report($e);

            return back(303);
        }

        $this->toast('Pipeline imported successfully!');

        return redirect()->route('dashboard.import.pipelines.show', ['pipeline' => $pipeline->id]);
    }

    /**
     * Create a pipeline and its step configs from the flattened YAML export array.
     *
     * @param  array<string, mixed>  $data
     */
    private function createPipelineFromYamlArray(array $data): ImportPipeline
    {
        $targetId = $data['target_id'];

        if (! $targetId) {
            throw new \InvalidArgumentException('target_id is required in YAML or associated with the authenticated user.');
        }

        $frequencyValue = $data['frequency'] ?? ImportPipelineFrequency::ONCE->value;
        $frequency = ImportPipelineFrequency::from($frequencyValue);

        $pipelineData = [
            'target_id' => $targetId,
            'name' => $data['name'].' (copy)' ?? 'Imported pipeline',
            'description' => $data['description'] ?? null,
            'is_active' => false,
            'start_time' => $data['start_time'] ?? null,
            'frequency' => $frequency,
        ];

        $pipeline = $this->dashboardService->createPipeline($pipelineData);

        $config = $data['config'] ?? [];

        foreach ($config as $stepConfig) {
            ImportPipelineConfig::query()->create([...Arr::except($stepConfig, 'id'), $stepConfig => $pipeline->id]);
        }

        return $pipeline->fresh();
    }

    /**
     * Show activity logs for the pipeline.
     */
    public function activityLogs(ImportPipeline $pipeline, Request $request): Response
    {
        $perPage = $request->get('per_page', 15);

        // Get pipeline activities
        $pipelineActivities = Activity::query()
            ->where('subject_type', ImportPipeline::class)
            ->where('subject_id', $pipeline->id);

        // Get config activities (they have subject_id pointing to pipeline via tap)
        $configActivities = Activity::query()
            ->where('log_name', 'pipeline_config')
            ->where('subject_id', $pipeline->id);

        // Get config activities where subject is the config itself
        $configIds = $pipeline->config()->pluck('id');
        $configSubjectActivities = Activity::query()
            ->where('subject_type', ImportPipelineConfig::class)
            ->whereIn('subject_id', $configIds);

        // Union all activities and order by created_at desc
        $activities = Activity::query()
            ->where(function ($query) use ($pipeline, $configIds) {
                $query->where(function ($q) use ($pipeline) {
                    $q->where('subject_type', ImportPipeline::class)
                        ->where('subject_id', $pipeline->id);
                })
                    ->orWhere(function ($q) use ($pipeline) {
                        $q->where('log_name', 'pipeline_config')
                            ->where('subject_id', $pipeline->id);
                    })
                    ->orWhere(function ($q) use ($configIds) {
                        $q->where('subject_type', ImportPipelineConfig::class)
                            ->whereIn('subject_id', $configIds);
                    });
            })
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return inertia(
            'Dashboard/Import/Pipelines/ActivityLogs',
            new ListActivityLogViewModel($activities, new PipelineViewModel($pipeline))
        );
    }

    /**
     * Show a single activity log entry.
     */
    public function showActivityLog(ImportPipeline $pipeline, int $activity): Response|RedirectResponse
    {
        $activityModel = Activity::query()
            ->with(['causer', 'subject'])
            ->find($activity);

        if (! $activityModel) {
            $this->toast('Activity log not found', ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.activity-logs', ['pipeline' => $pipeline->id]);
        }

        // Verify the activity belongs to this pipeline
        $isPipelineActivity = $activityModel->subject_type === ImportPipeline::class
            && $activityModel->subject_id === $pipeline->id;

        $isConfigActivity = $activityModel->subject_type === ImportPipelineConfig::class
            && $pipeline->config()->where('id', $activityModel->subject_id)->exists();

        $isConfigLogActivity = $activityModel->log_name === 'pipeline_config'
            && $activityModel->subject_id === $pipeline->id;

        if (! $isPipelineActivity && ! $isConfigActivity && ! $isConfigLogActivity) {
            $this->toast('Activity log does not belong to this pipeline', ToastNotificationVariant::Destructive);

            return redirect()->route('dashboard.import.pipelines.activity-logs', ['pipeline' => $pipeline->id]);
        }

        return inertia('Dashboard/Import/Pipelines/ActivityLogDetail', [
            'pipeline' => new PipelineViewModel($pipeline),
            'activity' => new ActivityLogViewModel($activityModel),
        ]);
    }
}
