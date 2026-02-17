<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Organization;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password when editing'),

                        Select::make('organization_uuid')
                            ->label('Organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Toggle::make('email_verified_at')
                            ->label('Email Verified')
                            ->dehydrateStateUsing(fn ($state) => $state ? now() : null)
                            ->formatStateUsing(fn ($state) => $state !== null)
                            ->afterStateHydrated(function ($component, $state) {
                                $component->state($state !== null);
                            }),

                        Select::make('role')
                            ->label('Role')
                            ->options(function () {
                                return Role::where('guard_name', 'web')
                                    ->whereNotIn('name', ['Super Admin', 'Dev'])
                                    ->pluck('name', 'name')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Select a role for this user'),
                    ]),
            ]);
    }
}
