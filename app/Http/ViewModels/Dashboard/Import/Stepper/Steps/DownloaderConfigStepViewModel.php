<?php

namespace App\Http\ViewModels\Dashboard\Import\Stepper\Steps;

use Elaitech\Import\Enums\ImportPipelineStep;
use App\Http\ViewModels\Dashboard\Import\PipelineViewModel;
use App\Http\ViewModels\Dashboard\Import\Stepper\CreateStepViewModel;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class DownloaderConfigStepViewModel extends ViewModel
{
    private Collection $downloaderConfig;

    public function __construct(
        private readonly ImportPipeline $pipeline
    ) {
        $this->downloaderConfig = $this->getDownloaderConfig();
    }

    public function pipeline(): PipelineViewModel
    {
        return new PipelineViewModel($this->pipeline);
    }

    public function stepper(): CreateStepViewModel
    {
        return resolve(CreateStepViewModel::class, [
            'pipeline' => $this->pipeline,
            'step' => ImportPipelineStep::DownloaderConfig,
        ]);
    }

    /**
     * Get current downloader type.
     */
    public function downloaderType(): string
    {
        return $this->downloaderConfig->get('downloader_type', 'https');
    }

    /**
     * Get current configuration values.
     */
    public function config(): Collection
    {
        return $this->downloaderConfig;
    }

    /**
     * Get unified source value from options.
     */
    public function source(): ?string
    {
        return $this->downloaderConfig->get('options')['source'] ?? null;
    }

    /**
     * Get host value.
     */
    public function host(): ?string
    {
        return $this->downloaderConfig->get('options')['host'] ?? null;
    }

    /**
     * Get port value.
     */
    public function port(): ?int
    {
        return $this->downloaderConfig->get('options')['port'] ?? null;
    }

    /**
     * Get username value.
     */
    public function username(): ?string
    {
        return $this->downloaderConfig->get('options')['username'] ?? null;
    }

    /**
     * Get password value.
     */
    public function password(): ?string
    {
        return $this->downloaderConfig->get('options')['password'] ?? null;
    }

    /**
     * Get file value (for FTP/SFTP credentials).
     */
    public function file(): ?string
    {
        return $this->downloaderConfig->get('options')['file'] ?? null;
    }

    /**
     * Get timeout value.
     */
    public function timeout(): int
    {
        return $this->downloaderConfig->get('options')['timeout'] ?? 30;
    }

    /**
     * Get retry attempts value.
     */
    public function retryAttempts(): int
    {
        return $this->downloaderConfig->get('options')['retry_attempts'] ?? 3;
    }

    /**
     * Get HTTP headers.
     */
    public function headers(): array
    {
        return $this->downloaderConfig->get('options')['headers'] ?? [];
    }

    /**
     * Get HTTP method.
     */
    public function method(): string
    {
        return $this->downloaderConfig->get('options')['method'] ?? 'GET';
    }

    /**
     * Get HTTP body.
     */
    public function body(): ?string
    {
        return $this->downloaderConfig->get('options')['body'] ?? null;
    }

    /**
     * Get query parameters.
     */
    public function queryParams(): array
    {
        return $this->downloaderConfig->get('options')['query_params'] ?? [];
    }

    /**
     * Get SSL verification setting.
     */
    public function verifySsl(): bool
    {
        return $this->downloaderConfig->get('options')['verify_ssl'] ?? true;
    }

    /**
     * Get follow redirects setting.
     */
    public function followRedirects(): bool
    {
        return $this->downloaderConfig->get('options')['follow_redirects'] ?? true;
    }

    /**
     * Get the downloader configuration from the pipeline.
     */
    private function getDownloaderConfig(): Collection
    {
        $config = $this->pipeline->getDownloaderConfig();

        if (! $config) {
            return collect([]);
        }

        return collect($config->config_data ?? []);
    }
}
