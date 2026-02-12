<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share([
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name ?? (method_exists($request->user(), 'first_name') ? trim(($request->user()->first_name ?? '').' '.($request->user()->last_name ?? '')) : null),
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
                    'roles' => $request->user()->getRoleNames()->toArray(),
                    'organization_uuid' => $request->user()->organization_uuid,
                    'organization_name' => $request->user()->organization?->name,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
            'toastNotifications' => fn () => $request->session()->get('toastNotifications'),
        ]);

        return $next($request);
    }
}
