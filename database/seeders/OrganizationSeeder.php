<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

final class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Organization',
                'settings' => null,
            ]
        );
    }
}
