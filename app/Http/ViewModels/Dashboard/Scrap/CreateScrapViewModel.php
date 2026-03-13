<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Scrap;

use App\Models\Dealer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class CreateScrapViewModel extends ViewModel
{
    public function dealers(): array
    {
        return Dealer::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->name])
            ->toArray();
    }
}
