<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Scrap;

use App\Models\Dealer;
use App\Models\Scrap;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class EditScrapViewModel extends ViewModel
{
    public function __construct(private readonly Scrap $scrap) {}

    public function scrap(): ScrapViewModel
    {
        return new ScrapViewModel($this->scrap);
    }

    public function dealers(): array
    {
        return Dealer::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }
}
