<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $admin;
    private Role $adminRole;
    private Role $pipelineManagerRole;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions and roles
        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view organization users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view pipelines', 'guard_name' => 'web']);

        $this->adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $this->pipelineManagerRole = Role::firstOrCreate(['name' => 'Pipeline Manager', 'guard_name' => 'web']);
        $devRole = Role::firstOrCreate(['name' => 'Dev', 'guard_name' => 'web']);

        $this->adminRole->givePermissionTo(['view organization users']);
        $devRole->givePermissionTo(['manage users', 'view organization users', 'view pipelines']);

        $this->organization = Organization::factory()->create();
        $this->admin = User::factory()->create(['organization_uuid' => $this->organization->uuid]);
        $this->admin->assignRole($devRole);
    }

    public function test_admin_can_view_users_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/dashboard/organization/users');

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_view_users(): void
    {
        $regularUser = User::factory()->create(['organization_uuid' => $this->organization->uuid]);
        $regularUser->assignRole($this->pipelineManagerRole);

        $response = $this->actingAs($regularUser)
            ->get('/dashboard/organization/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/dashboard/organization/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Admin',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'organization_uuid' => $this->organization->uuid,
        ]);

        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($newUser->hasRole('Admin'));
    }

    public function test_cannot_assign_protected_roles(): void
    {
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $response = $this->actingAs($this->admin)
            ->post('/dashboard/organization/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Super Admin',
            ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create(['organization_uuid' => $this->organization->uuid]);
        $user->assignRole($this->adminRole);

        $response = $this->actingAs($this->admin)
            ->put("/dashboard/organization/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => 'Pipeline Manager',
            ]);

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertTrue($user->hasRole('Pipeline Manager'));
        $this->assertFalse($user->hasRole('Admin'));
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create(['organization_uuid' => $this->organization->uuid]);

        $response = $this->actingAs($this->admin)
            ->delete("/dashboard/organization/users/{$user->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete("/dashboard/organization/users/{$this->admin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_cannot_update_user_from_different_org(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_uuid' => $otherOrg->uuid]);
        $otherUser->assignRole($this->adminRole);

        $response = $this->actingAs($this->admin)
            ->put("/dashboard/organization/users/{$otherUser->id}", [
                'name' => 'Hacked Name',
                'email' => $otherUser->email,
                'role' => 'Admin',
            ]);

        $response->assertStatus(403);

        $otherUser->refresh();
        $this->assertNotEquals('Hacked Name', $otherUser->name);
    }

    public function test_cannot_delete_user_from_different_org(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_uuid' => $otherOrg->uuid]);

        $response = $this->actingAs($this->admin)
            ->delete("/dashboard/organization/users/{$otherUser->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_create_user_requires_valid_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/dashboard/organization/users', [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
                'role' => 'Invalid Role',
            ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }
}
