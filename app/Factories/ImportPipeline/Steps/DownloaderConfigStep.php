<?php

namespace App\Factories\ImportPipeline\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Factories\ImportPipeline\AbstractImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\Stepper\Steps\DownloaderConfigStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Spatie\ViewModels\ViewModel;

class DownloaderConfigStep extends AbstractImportPipelineStep
{
    /**
     * Get the view model for the step.
     */
    public function getViewModel(ImportPipeline $pipeline): ViewModel
    {
        return new DownloaderConfigStepViewModel($pipeline);
    }

    /**
     * Process the step data.
     */
    public function process(ImportPipeline $pipeline, array $data): ImportPipeline
    {
        $pipeline->config()->updateOrCreate(
            ['type' => ImportPipelineStep::DownloaderConfig->value],
            ['config_data' => $data]
        );

        return $pipeline->fresh();
    }

    /**
     * Get the validation rules for the step.
     */
    public function getValidationRules(): array
    {
        return [
            'downloader_type' => ['required', 'string', 'in:http,https,ftp,sftp,local'],

            // Options validation - source for file path, file for FTP/SFTP credentials
            'options' => ['required', 'array'],
            'options.source' => ['nullable', 'required_if:downloader_type,http,https', 'string', 'min:1'],
            'options.host' => ['nullable', 'required_if:downloader_type,ftp,sftp', 'nullable', 'string'],
            'options.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'options.username' => ['required_if:downloader_type,ftp,sftp', 'nullable', 'string'],
            'options.password' => ['required_if:downloader_type,ftp,sftp', 'nullable', 'string'],
            'options.file' => ['required_if:downloader_type,ftp,sftp', 'nullable', 'string'],
            'options.timeout' => ['nullable', 'integer', 'min:1', 'max:300'],
            'options.retry_attempts' => ['nullable', 'integer', 'min:0', 'max:10'],

            // HTTP/HTTPS specific validation
            'method' => ['nullable', 'string', 'in:GET,POST,PUT,PATCH,DELETE,HEAD'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'body' => ['nullable', 'string'],
            'query_params' => ['nullable', 'array'],
            'query_params.*' => ['string'],
            'verify_ssl' => ['nullable', 'boolean'],
            'follow_redirects' => ['nullable', 'boolean'],

        ];
    }

    /**
     * Check if the step is available for the pipeline.
     */
    public function isAvailable(ImportPipeline $pipeline): bool
    {
        // Available if basic info is completed
        return (bool) $pipeline->name;
    }

    /**
     * Get the success message for completing the step.
     */
    public function getSuccessMessage(): string
    {
        return 'Downloader configuration saved successfully!';
    }

    public function getViewPath(): string
    {
        return 'Dashboard/Import/Pipelines/Steps/DownloaderConfigStep';
    }
}
