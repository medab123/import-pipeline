<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the user's current role
        $user = $this->record;
        $roles = $user->getRoleNames();
        
        if ($roles->isNotEmpty()) {
            $data['role'] = $roles->first();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store role separately before it gets removed
        $this->role = $data['role'] ?? null;
        
        // Remove role from data as it's not a direct model attribute
        unset($data['role']);

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->role ?? null;
        $user = $this->record;
        
        if ($role !== null) {
            // Only update roles if a role was explicitly selected
            // This allows clearing the role by selecting nothing (if you want that behavior)
            // Or keeping existing roles if the field is left unchanged
            $user->syncRoles($role ? [$role] : []);
        }
    }

    protected ?string $role = null;
}
