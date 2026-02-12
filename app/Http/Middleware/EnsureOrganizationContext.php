<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationContext
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has a valid, non-deleted organization
     * and binds it into the service container.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->organization_uuid) {
            abort(403, 'No organization context available.');
        }

        $organization = $user->organization;

        if (! $organization) {
            abort(403, 'Organization not found.');
        }

        if ($organization->trashed()) {
            abort(403, 'Organization has been deactivated.');
        }

        // Bind the organization into the container for global access
        app()->instance('organization', $organization);

        return $next($request);
    }
}
