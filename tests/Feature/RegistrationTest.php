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

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    }

    public function test_registration_page_renders(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register_with_organization(): void
    {
        $response = $this->post('/register', [
            'organization_name' => 'Test Company',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard/import/pipelines');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->organization_uuid);
        $this->assertTrue($user->hasRole('Admin'));

        $organization = Organization::where('uuid', $user->organization_uuid)->first();
        $this->assertNotNull($organization);
        $this->assertEquals('Test Company', $organization->name);
        $this->assertStringStartsWith('test-company-', $organization->slug);
    }

    public function test_registration_requires_organization_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('organization_name');
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->post('/register', [
            'organization_name' => 'Test Company',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'organization_name' => 'Test Company',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
