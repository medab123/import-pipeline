<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
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

        // Create permissions
        $permissions = [
            'manage users',
            'manage pipelines',
            'view pipelines',
            'create pipelines',
            'edit pipelines',
            'delete pipelines',
            'export pipelines',
            'import pipelines',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::query()->firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $devRole = Role::query()->firstOrCreate(['name' => 'Dev', 'guard_name' => 'web']);
        $pipelineManagerRole = Role::query()->firstOrCreate(['name' => 'Pipeline Manager', 'guard_name' => 'web']);

        $superAdminRole->givePermissionTo($permissions);
        $devRole->givePermissionTo($permissions);
        $adminRole->givePermissionTo(Arr::except($permissions, ['manage users', 'export pipelines', 'import pipelines']));
        $pipelineManagerRole->givePermissionTo(Arr::except($permissions, ['manage users']));
    }
}
