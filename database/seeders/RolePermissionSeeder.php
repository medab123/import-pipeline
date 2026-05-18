<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Organization
            'manage organization',
            'manage users',
            'view organization users',

            // Pipelines
            'manage pipelines',
            'view pipelines',
            'create pipelines',
            'edit pipelines',
            'delete pipelines',
            'export pipelines',
            'import pipelines',

            // Dealers
            'manage dealers',
            'view dealers',
            'manage scraps',
            'view scraps',
            'manage fbmp token',
        ];

        // Drop the legacy permission name in case it was previously seeded.
        Permission::query()->where('name', 'manage multi token')->delete();

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create or fetch roles, then sync permissions (idempotent: updates if exists, creates if not)
        $superAdminRole = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $devRole = Role::query()->firstOrCreate(['name' => 'Dev', 'guard_name' => 'web']);
        $pipelineManagerRole = Role::query()->firstOrCreate(['name' => 'Pipeline Manager', 'guard_name' => 'web']);
        $dealerManagerRole = Role::query()->firstOrCreate(['name' => 'Dealer Manager', 'guard_name' => 'web']);

        // Super Admin & Dev: all permissions
        $superAdminRole->syncPermissions($permissions);
        $devRole->syncPermissions($permissions);

        // Admin: everything except manage users and FBMP token management
        // (FBMP token create/revoke is Dev-only; everyone else can still view/regenerate).
        $adminRole->syncPermissions(collect($permissions)->reject(
            fn (string $p) => in_array($p, ['manage users', 'manage fbmp token'])
        )->values()->all());

        // Pipeline Manager: pipelines + dealers, no organization management
        $pipelineManagerRole->syncPermissions([
            'manage pipelines',
            'view pipelines',
            'create pipelines',
            'edit pipelines',
            'delete pipelines',
            'export pipelines',
            'import pipelines',
            'manage dealers',
            'view dealers',
            'manage scraps',
            'view scraps',
        ]);

        // Dealer Manager: only dealers and scraps
        $dealerManagerRole->syncPermissions([
            'manage dealers',
            'view dealers',
            'manage scraps',
            'view scraps',
        ]);

        // Reset cached permissions after sync
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
