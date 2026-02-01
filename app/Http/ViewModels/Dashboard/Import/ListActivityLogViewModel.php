<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use App\Http\ViewModels\PaginatorViewModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ListActivityLogViewModel extends ViewModel
{
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly PipelineViewModel $pipeline
    ) {}

    public function logs(): Collection
    {
        return $this->paginator->getCollection()
            ->mapInto(ActivityLogViewModel::class);
    }

    public function pipeline(): PipelineViewModel
    {
        return $this->pipeline;
    }

    public function paginator(): PaginatorViewModel
    {
        return new PaginatorViewModel($this->paginator);
    }
}
