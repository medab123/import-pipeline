<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\OrganizationToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateOrganizationToken
{
    /**
     * Handle an incoming request.
     *
     * Authenticates requests using an organization-level token from the
     * organization_tokens table. Each organization can have multiple tokens.
     * Token format: "Bearer org_{random_string}" or "org_{random_string}"
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token || ! $this->isValidTokenFormat($token)) {
            return response()->json([
                'message' => 'Organization token is required.',
            ], 401);
        }

        $organizationToken = OrganizationToken::with('organization')->where('token', $token)->first();

        if (! $organizationToken) {
            return response()->json([
                'message' => 'Invalid or expired organization token.',
            ], 401);
        }

        $organization = $organizationToken->organization;

        if (! $organization) {
            return response()->json([
                'message' => 'Organization not found for this token.',
            ], 404);
        }

        if ($organization->trashed()) {
            return response()->json([
                'message' => 'Organization has been deactivated.',
            ], 403);
        }

        // Track token usage
        $organizationToken->forceFill(['last_used_at' => now()])->saveQuietly();

        // Bind the authenticated organization and token into the container
        app()->instance('organization', $organization);
        app()->instance('auth_token', $organizationToken);

        return $next($request);
    }

    /**
     * Extract the token from the request.
     */
    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if ($authorization) {
            if (preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
                return trim($matches[1]);
            }

            return trim($authorization);
        }

        return $request->header('X-Organization-Token');
    }

    /**
     * Validate token format.
     */
    private function isValidTokenFormat(?string $token): bool
    {
        return $token && str_starts_with($token, 'org_') && strlen($token) > 4;
    }
}
