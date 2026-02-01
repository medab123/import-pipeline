<?php

namespace App\Http\ViewModels;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
class PaginatorViewModel extends ViewModel
{
    public function __construct(private readonly LengthAwarePaginator $paginator)
    {
        //
    }

    public function currentPage(): int
    {
        return $this->paginator->currentPage();
    }

    public function hasMorePages(): bool
    {
        return $this->paginator->hasMorePages();
    }

    public function lastPage(): int
    {
        return $this->paginator->lastPage();
    }

    public function perPage(): int
    {
        return $this->paginator->perPage();
    }

    public function total(): int
    {
        return $this->paginator->total();
    }

    public function nextPageUrl(): ?string
    {
        return $this->paginator->nextPageUrl();
    }

    public function previousPageUrl(): ?string
    {
        return $this->paginator->previousPageUrl();
    }
}
