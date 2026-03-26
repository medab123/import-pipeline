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
     * Display organization API tokens.
     */
    public function index(Request $request): Response
    {
        $organization = app('organization');

        $tokens = OrganizationToken::where('organization_uuid', $organization->uuid)
            ->orderByDesc('created_at')
            ->paginate(15);

        return Inertia::render('Dashboard/Organization/Tokens/Index', [
            'tokens' => $tokens->through(fn (OrganizationToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'description' => $token->description,
                'token' => $token->token,
                'token_preview' => Str::mask($token->token, '*', 12),
                'last_used_at' => $token->last_used_at?->format('M d, Y H:i'),
                'created_at' => $token->created_at?->format('M d, Y'),
            ]),
            'paginator' => [
                'currentPage' => $tokens->currentPage(),
                'hasMorePages' => $tokens->hasMorePages(),
                'lastPage' => $tokens->lastPage(),
                'perPage' => $tokens->perPage(),
                'total' => $tokens->total(),
                'nextPageUrl' => $tokens->nextPageUrl(),
                'previousPageUrl' => $tokens->previousPageUrl(),
            ],
            'organizationName' => $organization->name,
            // Flash the newly created token so it can be shown once
            'newToken' => session('newToken'),
        ]);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): RedirectResponse
    {
        $organization = app('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $plainToken = 'org_'.Str::random(40);

        $token = OrganizationToken::create([
            'organization_uuid' => $organization->uuid,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'token' => $plainToken,
        ]);

        return redirect()
            ->route('dashboard.organization.tokens.index')
            ->with('newToken', $plainToken)
            ->with('toastNotifications', [[
                'title' => 'Token Created',
                'message' => "API token \"{$token->name}\" has been created. Copy it now — it won't be shown again.",
                'variant' => 'default',
            ]]);
    }

    /**
     * Revoke (delete) an API token.
     */
    public function destroy(OrganizationToken $organizationToken): RedirectResponse
    {
        $organization = app('organization');

        // Ensure the token belongs to the current organization
        if ($organizationToken->organization_uuid !== $organization->uuid) {
            abort(404);
        }

        $name = $organizationToken->name;
        $organizationToken->delete();

        return redirect()->back()->with('toastNotifications', [[
            'title' => 'Token Revoked',
            'message' => "API token \"{$name}\" has been revoked.",
            'variant' => 'default',
        ]]);
    }
}
