<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the default organization
        $organization = Organization::where('slug', 'default')->first();

        // Get roles
        $superAdminRole = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $devRole = Role::where('name', 'Dev')->where('guard_name', 'web')->first();

        $mohammed = User::updateOrCreate(
            ['email' => 'mohammed.elabidi123@gmail.com'],
            [
                'name' => 'Elabidi Mohammed',
                'password' => Hash::make('password'),
                'organization_uuid' => $organization?->uuid,
            ]
        );

        if ($superAdminRole && ! $mohammed->hasRole('Dev')) {
            $mohammed->assignRole($devRole);
        }
    }
}

