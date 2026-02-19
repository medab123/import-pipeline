<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationToken;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class OrganizationTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the default organization
        $organization = Organization::where('slug', 'default')->first();

        if (! $organization) {
            $this->command->warn('Default organization not found. Please run OrganizationSeeder first.');
            return;
        }

        // Get available pipelines for the organization
        $pipelines = ImportPipeline::where('organization_uuid', $organization->uuid)->get();

        // Create a token with access to all pipelines (no restrictions)
        $this->createToken(
            organization: $organization,
            name: 'Development Token',
            expiresInDays: 365,
            pipelineIds: []
        );

        // Create a token with specific pipeline access (if pipelines exist)
        if ($pipelines->isNotEmpty()) {
            $pipelineIds = $pipelines->take(2)->pluck('id')->toArray();
            
            $this->createToken(
                organization: $organization,
                name: 'Limited Access Token',
                expiresInDays: 90,
                pipelineIds: $pipelineIds
            );
        }

        // Create a token without expiration
        $this->createToken(
            organization: $organization,
            name: 'Long-term Token',
            expiresInDays: null,
            pipelineIds: []
        );

        $this->command->info('Organization tokens seeded successfully.');
    }

    /**
     * Create an organization token.
     */
    private function createToken(
        Organization $organization,
        string $name,
        ?int $expiresInDays,
        array $pipelineIds
    ): OrganizationToken {
        // Generate a random plain-text token
        // Format: org_{random_string}
        $plainTextToken = 'org_' . Str::random(40);
        
        // Hash it for DB storage (SHA-256 is common for API keys)
        $hashedToken = hash('sha256', $plainTextToken);

        // Calculate expiration date
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        // Create the token
        $token = OrganizationToken::create([
            'organization_uuid' => $organization->uuid,
            'name' => $name,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        // Attach pipelines if provided
        if (! empty($pipelineIds)) {
            // Validate that pipeline IDs belong to the organization
            $validPipelineIds = ImportPipeline::where('organization_uuid', $organization->uuid)
                ->whereIn('id', $pipelineIds)
                ->pluck('id')
                ->toArray();

            if (! empty($validPipelineIds)) {
                $token->pipelines()->attach($validPipelineIds);
            }
        }

        // Output the plain text token for development/testing
        $this->command->line("Created token: {$name}");
        $this->command->line("  Token: {$plainTextToken}");
        $this->command->line("  Expires: " . ($expiresAt ? $expiresAt->format('Y-m-d H:i:s') : 'Never'));
        $this->command->line("  Pipelines: " . (empty($pipelineIds) ? 'All' : count($pipelineIds)));

        return $token;
    }
}
