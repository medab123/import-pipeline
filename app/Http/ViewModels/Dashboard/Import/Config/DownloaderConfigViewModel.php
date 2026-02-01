<?php

namespace App\Http\ViewModels\Dashboard\Import\Config;

use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Support\Collection;
use Spatie\ViewModels\ViewModel;

class DownloaderConfigViewModel extends ViewModel
{
    private Collection $config;

    public function __construct(private ImportPipeline $pipeline)
    {
        $this->config = collect($pipeline->config);
    }

    public function type()
    {
        return $this->config->get('type');
    }

    public function url()
    {
        return $this->config->get('url');
    }

    public function headers()
    {
        return $this->config->get('headers');
    }

    public function body()
    {
        return $this->config->get('body');
    }

    public function method()
    {
        return $this->config->get('method');
    }

    public function timeout(): int
    {
        return $this->config->get('timeout', 30);
    }
}
