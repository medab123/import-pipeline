<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store role separately before it gets removed
        $this->role = $data['role'] ?? null;
        
        // Remove role from data as it's not a direct model attribute
        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $role = $this->role ?? null;
        
        if ($role) {
            $this->record->assignRole($role);
        }
    }

    protected ?string $role = null;
}
