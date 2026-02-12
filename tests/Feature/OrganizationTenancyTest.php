<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['name' => 'Org A', 'slug' => 'org-a']);
        $this->orgB = Organization::factory()->create(['name' => 'Org B', 'slug' => 'org-b']);

        $this->userA = User::factory()->create(['organization_uuid' => $this->orgA->uuid]);
        $this->userB = User::factory()->create(['organization_uuid' => $this->orgB->uuid]);
    }

    /**
     * Helper to create a pipeline bypassing mass-assignment protection.
     * Package models don't have organization_uuid in $fillable.
     */
    private function createPipelineForOrg(Organization $org, array $attributes = []): ImportPipeline
    {
        $pipeline = new ImportPipeline();
        $pipeline->forceFill(array_merge([
            'name' => 'Test Pipeline',
            'frequency' => 'once',
            'organization_uuid' => $org->uuid,
        ], $attributes));
        $pipeline->saveQuietly(); // skip observers to avoid auto-assign overwriting

        return $pipeline->fresh();
    }

    // -------------------------------------------------------
    // Global Scope Tests
    // -------------------------------------------------------

    public function test_global_scope_filters_pipelines_by_organization(): void
    {
        $pipelineA = $this->createPipelineForOrg($this->orgA, ['name' => 'Pipeline A']);
        $pipelineB = $this->createPipelineForOrg($this->orgB, ['name' => 'Pipeline B']);

        // Act as user A — should only see pipeline A
        $this->actingAs($this->userA);

        $pipelines = ImportPipeline::all();

        $this->assertCount(1, $pipelines);
        $this->assertEquals($pipelineA->id, $pipelines->first()->id);
        $this->assertEquals('Pipeline A', $pipelines->first()->name);
    }

    public function test_user_cannot_access_another_organizations_pipeline(): void
    {
        $pipelineB = $this->createPipelineForOrg($this->orgB, ['name' => 'Pipeline B']);

        // Act as user A — should not find pipeline B
        $this->actingAs($this->userA);

        $found = ImportPipeline::find($pipelineB->id);

        $this->assertNull($found);
    }

    // -------------------------------------------------------
    // Auto-Assignment Tests
    // -------------------------------------------------------

    public function test_organization_uuid_auto_assigns_on_pipeline_creation(): void
    {
        $this->actingAs($this->userA);

        // Use forceFill since organization_uuid isn't in package model's $fillable
        $pipeline = new ImportPipeline();
        $pipeline->forceFill([
            'name' => 'Auto-Assigned Pipeline',
            'frequency' => 'once',
        ]);
        $pipeline->save();

        $this->assertEquals($this->orgA->uuid, $pipeline->organization_uuid);
    }

    public function test_organization_uuid_not_overwritten_if_already_set(): void
    {
        $this->actingAs($this->userA);

        // Pre-set the org uuid before save — the observer should respect it
        $pipeline = new ImportPipeline();
        $pipeline->forceFill([
            'name' => 'Explicit Org Pipeline',
            'frequency' => 'once',
            'organization_uuid' => $this->orgB->uuid,
        ]);
        $pipeline->save();

        $this->assertEquals($this->orgB->uuid, $pipeline->organization_uuid);
    }

    // -------------------------------------------------------
    // Policy Tests
    // -------------------------------------------------------

    public function test_policy_denies_access_to_cross_organization_pipeline(): void
    {
        $pipelineB = $this->createPipelineForOrg($this->orgB, ['name' => 'Pipeline B']);

        $this->assertFalse($this->userA->can('view', $pipelineB));
        $this->assertFalse($this->userA->can('update', $pipelineB));
        $this->assertFalse($this->userA->can('delete', $pipelineB));
        $this->assertFalse($this->userA->can('export', $pipelineB));
    }

    public function test_policy_allows_access_to_own_organization_pipeline(): void
    {
        $pipelineA = $this->createPipelineForOrg($this->orgA, ['name' => 'Pipeline A']);

        // Need to explicitly test via the policy, not Gate (which Spatie may intercept)
        $policy = new \App\Policies\ImportPipelinePolicy();

        $this->assertTrue($policy->view($this->userA, $pipelineA));
        $this->assertTrue($policy->update($this->userA, $pipelineA));
        $this->assertTrue($policy->delete($this->userA, $pipelineA));
        $this->assertTrue($policy->export($this->userA, $pipelineA));
    }

    public function test_policy_allows_create_for_user_with_organization(): void
    {
        $policy = new \App\Policies\ImportPipelinePolicy();

        $this->assertTrue($policy->create($this->userA));
    }

    // -------------------------------------------------------
    // Middleware Tests
    // -------------------------------------------------------

    public function test_middleware_blocks_user_without_organization(): void
    {
        $userWithoutOrg = User::factory()->create(['organization_uuid' => null]);

        $response = $this->actingAs($userWithoutOrg)
            ->get('/dashboard/import/pipelines');

        $response->assertStatus(403);
    }

    public function test_soft_deleted_organization_blocks_access(): void
    {
        $this->orgA->delete(); // soft delete

        $response = $this->actingAs($this->userA)
            ->get('/dashboard/import/pipelines');

        $response->assertStatus(403);
    }

    public function test_middleware_allows_user_with_valid_organization(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/dashboard/import/pipelines');

        // Should not be 403 (could be 200 or redirect depending on Inertia)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // -------------------------------------------------------
    // Organization Model Tests
    // -------------------------------------------------------

    public function test_organization_uses_uuid_primary_key(): void
    {
        $org = Organization::factory()->create();

        $this->assertIsString($org->uuid);
        $this->assertEquals(36, strlen($org->uuid)); // UUID v4 length
        $this->assertFalse($org->incrementing);
        $this->assertEquals('string', $org->getKeyType());
    }

    public function test_user_belongs_to_organization(): void
    {
        $this->assertNotNull($this->userA->organization);
        $this->assertEquals($this->orgA->uuid, $this->userA->organization->uuid);
        $this->assertEquals('Org A', $this->userA->organization->name);
    }

    public function test_organization_has_many_users(): void
    {
        $users = $this->orgA->users;

        $this->assertCount(1, $users);
        $this->assertEquals($this->userA->id, $users->first()->id);
    }
}

