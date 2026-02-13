<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Organization;

use App\Http\Controllers\Controller;
use App\Models\OrganizationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->latest()
            ->paginate(15)
            ->through(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at->format('M d, Y H:i'),
                'last_used_at' => $token->last_used_at ? $token->last_used_at->format('M d, Y H:i') : 'Never',
                'expires_at' => $token->expires_at?->format('M d, Y H:i'),
            ]);

        return Inertia::render('Dashboard/Organization/Tokens/Index', [
            'tokens' => $tokens,
            'organizationName' => $organization->name,
            'newlyCreatedToken' => session('newlyCreatedToken'), // Passed via flash
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
        ]);

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
            // 'expires_at' => now()->addYear(), // Optional expiration
        ]);

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
