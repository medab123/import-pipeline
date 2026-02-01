<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use App\Http\ViewModels\PaginatorViewModel;
use App\Http\ViewModels\PipelineStatsViewModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ListPipelineViewModel extends ViewModel
{
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly ?PipelineStatsViewModel $stats = null,
    ) {}

    /**
     * @return Collection<PipelineViewModel>
     */
    public function pipelines(): Collection
    {
        return $this->paginator
            ->getCollection()
            ->mapInto(PipelineViewModel::class);
    }

    public function paginator(): PaginatorViewModel
    {
        return new PaginatorViewModel($this->paginator);
    }

    public function stats(): ?PipelineStatsViewModel
    {
        return $this->stats;
    }
}
