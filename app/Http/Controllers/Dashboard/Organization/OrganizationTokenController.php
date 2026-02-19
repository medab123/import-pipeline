<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Organization;

use App\Http\Controllers\Controller;
use App\Models\OrganizationToken;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationTokenController extends Controller
{
    /**
     * Display a listing of organization tokens.
     */
    public function index(Request $request): Response
    {
        $organization = app('organization');

        $tokens = OrganizationToken::where('organization_uuid', $organization->uuid)
            ->with('pipelines:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at->format('M d, Y H:i'),
                'last_used_at' => $token->last_used_at ? $token->last_used_at->format('M d, Y H:i') : 'Never',
                'expires_at' => $token->expires_at?->format('M d, Y H:i'),
                'is_expired' => $token->isExpired(),
                'pipelines' => $token->pipelines->map(fn ($pipeline) => [
                    'id' => $pipeline->id,
                    'name' => $pipeline->name,
                ]),
                'has_all_pipelines_access' => $token->pipelines->count() === 0,
            ]);

        // Get available pipelines for the organization
        $availablePipelines = ImportPipeline::where('organization_uuid', $organization->uuid)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard/Organization/Tokens/Index', [
            'tokens' => $tokens,
            'organizationName' => $organization->name,
            'availablePipelines' => $availablePipelines,
            'newlyCreatedToken' => session('newlyCreatedToken'), // Passed via flash
        ]);
    }

    /**
     * Display the specified token.
     */
    public function show(OrganizationToken $token): Response
    {
        $organizationToken = $token;
        $this->ensureBelongsToOrganization($organizationToken);

        $organization = app('organization');

        $organizationToken->load('pipelines:id,name');

        return Inertia::render('Dashboard/Organization/Tokens/Show', [
            'token' => [
                'id' => $organizationToken->id,
                'name' => $organizationToken->name,
                'created_at' => $organizationToken->created_at->format('M d, Y H:i:s'),
                'last_used_at' => $organizationToken->last_used_at
                    ? $organizationToken->last_used_at->format('M d, Y H:i:s')
                    : null,
                'expires_at' => $organizationToken->expires_at
                    ? $organizationToken->expires_at->format('M d, Y H:i:s')
                    : null,
                'is_expired' => $organizationToken->isExpired(),
                'is_valid' => $organizationToken->isValid(),
                'pipelines' => $organizationToken->pipelines->map(fn ($pipeline) => [
                    'id' => $pipeline->id,
                    'name' => $pipeline->name,
                ]),
                'has_all_pipelines_access' => $organizationToken->pipelines->count() === 0,
                'pipeline_count' => $organizationToken->pipelines->count(),
            ],
            'organizationName' => $organization->name,
        ]);
    }

    /**
     * Store a newly created token in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $organization = app('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'], // Max 10 years
            'pipeline_ids' => ['nullable', 'array'],
            'pipeline_ids.*' => ['required', 'integer', 'exists:import_pipelines,id'],
        ]);

        // Calculate expiration date
        $expiresAt = null;
        if ($request->filled('expires_at')) {
            $expiresAt = Carbon::parse($validated['expires_at']);
        } elseif ($request->filled('expires_in_days')) {
            $expiresAt = now()->addDays($validated['expires_in_days']);
        }

        // Validate that pipeline_ids belong to the organization
        if ($request->filled('pipeline_ids')) {
            $pipelineIds = $validated['pipeline_ids'];
            $validPipelineCount = ImportPipeline::where('organization_uuid', $organization->uuid)
                ->whereIn('id', $pipelineIds)
                ->count();

            if ($validPipelineCount !== count($pipelineIds)) {
                return redirect()->back()
                    ->withErrors(['pipeline_ids' => 'One or more selected pipelines do not belong to this organization.'])
                    ->withInput();
            }
        }

        // Generate a random plain-text token
        // Format: org_{random_string}
        $plainTextToken = 'org_' . Str::random(40);

        // Hash it for DB storage (SHA-256 is common for API keys)
        // We only store the hash, so we can't retrieve the original later
        $hashedToken = hash('sha256', $plainTextToken);

        $organizationToken = OrganizationToken::create([
            'organization_uuid' => $organization->uuid,
            'name' => $validated['name'],
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
        ]);

        // Attach pipelines if provided
        if ($request->filled('pipeline_ids')) {
            $organizationToken->pipelines()->attach($validated['pipeline_ids']);
        }

        // Flash the plain text token ONLY once to the session for the user to see
        return redirect()->back()
            ->with('toastNotifications', [[
                'title' => 'Token Generated',
                'message' => 'New API token has been created. Copy it now!',
                'variant' => 'default',
            ]])
            ->with('newlyCreatedToken', [
                'name' => $organizationToken->name,
                'token' => $plainTextToken,
            ]);
    }

    /**
     * Show the form for editing the specified token.
     */
    public function edit(OrganizationToken $token): Response
    {
        $organizationToken = $token;
        $this->ensureBelongsToOrganization($organizationToken);

        $organization = app('organization');

        $organizationToken->load('pipelines:id,name');

        // Get available pipelines for the organization
        $availablePipelines = ImportPipeline::where('organization_uuid', $organization->uuid)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Dashboard/Organization/Tokens/Edit', [
            'token' => [
                'id' => $organizationToken->id,
                'name' => $organizationToken->name,
                'expires_at' => $organizationToken->expires_at?->format('Y-m-d\TH:i'),
                'expires_in_days' => $organizationToken->expires_at
                    ? now()->diffInDays($organizationToken->expires_at, false)
                    : null,
                'pipeline_ids' => $organizationToken->pipelines->pluck('id')->toArray(),
                'has_expiration' => $organizationToken->expires_at !== null,
            ],
            'availablePipelines' => $availablePipelines,
            'organizationName' => $organization->name,
        ]);
    }

    /**
     * Update the specified token in storage.
     */
    public function update(Request $request, OrganizationToken $token): RedirectResponse
    {
        $organizationToken = $token;
        $this->ensureBelongsToOrganization($organizationToken);

        $organization = app('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:3650'], // Max 10 years
            'pipeline_ids' => ['nullable', 'array'],
            'pipeline_ids.*' => ['required', 'integer', 'exists:import_pipelines,id'],
        ]);

        // Calculate expiration date
        $expiresAt = null;
        if ($request->filled('expires_at')) {
            $expiresAt = Carbon::parse($validated['expires_at']);
        } elseif ($request->filled('expires_in_days')) {
            $expiresAt = now()->addDays($validated['expires_in_days']);
        }

        // Validate that pipeline_ids belong to the organization
        if ($request->filled('pipeline_ids')) {
            $pipelineIds = $validated['pipeline_ids'];
            $validPipelineCount = ImportPipeline::where('organization_uuid', $organization->uuid)
                ->whereIn('id', $pipelineIds)
                ->count();

            if ($validPipelineCount !== count($pipelineIds)) {
                return redirect()->back()
                    ->withErrors(['pipeline_ids' => 'One or more selected pipelines do not belong to this organization.'])
                    ->withInput();
            }
        }

        // Update token
        $organizationToken->update([
            'name' => $validated['name'],
            'expires_at' => $expiresAt,
        ]);

        // Sync pipelines (this will replace existing associations)
        if ($request->filled('pipeline_ids')) {
            $organizationToken->pipelines()->sync($validated['pipeline_ids']);
        } else {
            // If no pipelines provided, remove all associations (allows access to all)
            $organizationToken->pipelines()->detach();
        }

        return redirect()->route('dashboard.organization.tokens.show', $organizationToken->id)
            ->with('toastNotifications', [[
                'title' => 'Token Updated',
                'message' => 'The token has been successfully updated.',
                'variant' => 'default',
            ]]);
    }

    /**
     * Remove the specified token from storage.
     */
    public function destroy(OrganizationToken $organizationToken): RedirectResponse
    {
        $this->ensureBelongsToOrganization($organizationToken);

        $organizationToken->delete();

        return redirect()->back()
            ->with('toastNotifications', [[
                'title' => 'Token Revoked',
                'message' => 'The token has been permanently revoked.',
                'variant' => 'default',
            ]]);
    }

    private function ensureBelongsToOrganization(OrganizationToken $token): void
    {
        $organization = app('organization');
        if ($token->organization_uuid !== $organization->uuid) {
            abort(403, 'Unauthorized action.');
        }
    }
}
