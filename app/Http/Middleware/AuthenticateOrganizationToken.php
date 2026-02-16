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
     * Authenticates requests using organization tokens from the Authorization header.
     * Token format: "Bearer org_{random_string}" or "org_{random_string}"
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'message' => 'Organization token is required.',
            ], 401);
        }

        if (! $this->isValidTokenFormat($token)) {
            return response()->json([
                'message' => 'Invalid token format.',
            ], 401);
        }

        $organizationToken = $this->validateToken($token);

        if (! $organizationToken) {
            return response()->json([
                'message' => 'Invalid or expired organization token.',
            ], 401);
        }

        $organization = $organizationToken->organization;

        if (! $organization) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
        }

        if ($organization->trashed()) {
            return response()->json([
                'message' => 'Organization has been deactivated.',
            ], 403);
        }

        // Update last used timestamp
        $organizationToken->update([
            'last_used_at' => now(),
        ]);

        // Bind the organization into the container for global access
        app()->instance('organization', $organization);
        app()->instance('organization_token', $organizationToken);

        return $next($request);
    }

    /**
     * Extract the token from the request.
     */
    private function extractToken(Request $request): ?string
    {
        // Try Authorization header first (Bearer token)
        $authorization = $request->header('Authorization');

        if ($authorization) {
            // Handle "Bearer org_..." format
            if (preg_match('/Bearer\s+(.+)/i', $authorization, $matches)) {
                return trim($matches[1]);
            }

            // Handle direct token in Authorization header
            return trim($authorization);
        }

        // Fallback to custom header
        return $request->header('X-Organization-Token');
    }

    /**
     * Validate token format.
     */
    private function isValidTokenFormat(?string $token): bool
    {
        if (! $token) {
            return false;
        }

        return str_starts_with($token, 'org_') && strlen($token) > 4;
    }

    /**
     * Validate the token against the database.
     */
    private function validateToken(string $token): ?OrganizationToken
    {
        $organizationToken = OrganizationToken::findByPlainTextToken($token);

        if (! $organizationToken || ! $organizationToken->isValid()) {
            return null;
        }

        return $organizationToken;
    }
}
