<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Scrap;

class ScrapObserver
{
    public function created(Scrap $scrap): void
    {
        $scrap->dealer?->resolveStatus();
    }

    public function deleted(Scrap $scrap): void
    {
        $scrap->dealer?->resolveStatus();
    }
}
