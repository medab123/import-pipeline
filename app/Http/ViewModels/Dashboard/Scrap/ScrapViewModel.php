<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Scrap;

use App\Models\Scrap;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ScrapViewModel extends ViewModel
{
    public function __construct(private readonly Scrap $scrap) {}

    public function id(): int
    {
        return $this->scrap->id;
    }

    public function dealerId(): int
    {
        return $this->scrap->dealer_id;
    }

    public function dealerName(): string
    {
        return $this->scrap->dealer?->name ?? '';
    }

    public function ftpFilePath(): string
    {
        return $this->scrap->ftp_file_path;
    }

    public function provider(): string
    {
        return $this->scrap->provider;
    }

    public function createdAt(): string
    {
        return $this->scrap->created_at->toISOString();
    }

    public function formattedCreatedAt(): string
    {
        return $this->scrap->created_at->format('M d, Y H:i');
    }

    public function updatedAt(): string
    {
        return $this->scrap->updated_at->toISOString();
    }

    public function formattedUpdatedAt(): string
    {
        return $this->scrap->updated_at->format('M d, Y H:i');
    }
}
