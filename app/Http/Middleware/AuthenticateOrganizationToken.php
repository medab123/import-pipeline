<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateOrganizationToken
{
    /**
     * Handle an incoming request.
     *
     * Authenticates requests using the pipeline token stored on import_pipelines.token.
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

        $pipeline = ImportPipeline::where('token', $token)->first();

        if (! $pipeline) {
            return response()->json([
                'message' => 'Invalid or expired organization token.',
            ], 401);
        }

        $organization = Organization::where('uuid', $pipeline->organization_uuid)->first();

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

        // Bind the authenticated pipeline and its organization into the container
        app()->instance('auth_pipeline', $pipeline);
        app()->instance('organization', $organization);

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
        if (! $token) {
            return false;
        }

        return str_starts_with($token, 'org_') && strlen($token) > 4;
    }
}
