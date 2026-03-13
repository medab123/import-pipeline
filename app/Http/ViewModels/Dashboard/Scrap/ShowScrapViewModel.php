<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Scrap;

use App\Models\Scrap;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ShowScrapViewModel extends ViewModel
{
    public function __construct(private readonly Scrap $scrap) {}

    public function scrap(): ScrapViewModel
    {
        return new ScrapViewModel($this->scrap);
    }
}
