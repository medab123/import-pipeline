<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Dealer;

use App\Http\ViewModels\PaginatorViewModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ListDealerViewModel extends ViewModel
{
    public function __construct(
        private readonly LengthAwarePaginator $paginator,
        private readonly ?string $search = null,
    ) {}

    /**
     * @return Collection<DealerViewModel>
     */
    public function dealers(): Collection
    {
        return $this->paginator
            ->getCollection()
            ->mapInto(DealerViewModel::class);
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
