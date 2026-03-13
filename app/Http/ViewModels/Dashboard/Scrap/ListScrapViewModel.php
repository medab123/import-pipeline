<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Scrap;

use App\Http\ViewModels\PaginatorViewModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ListScrapViewModel extends ViewModel
{
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly ?string $search = null,
    ) {}

    /**
     * @return Collection<ScrapViewModel>
     */
    public function scraps(): Collection
    {
        return $this->paginator
            ->getCollection()
            ->mapInto(ScrapViewModel::class);
    }

    public function paginator(): PaginatorViewModel
    {
        return new PaginatorViewModel($this->paginator);
    }

    public function filters(): array
    {
        return [
            'search' => $this->search,
        ];
    }
}
