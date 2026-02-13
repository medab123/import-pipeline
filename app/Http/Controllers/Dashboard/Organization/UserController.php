<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Organization;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class UserController extends Controller
{
    /**
     * Roles that cannot be assigned via the UI.
     */
    private const PROTECTED_ROLES = ['Super Admin', 'Dev'];

    /**
     * Display organization members.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $organization = app('organization');

        $users = User::where('organization_uuid', $organization->uuid)
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        $assignableRoles = Role::whereNotIn('name', self::PROTECTED_ROLES)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        return Inertia::render('Dashboard/Organization/Users/Index', [
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray(),
                'created_at' => $user->created_at?->format('M d, Y'),
                'is_current_user' => $user->id === $request->user()->id,
            ]),
            'paginator' => [
                'currentPage' => $users->currentPage(),
                'hasMorePages' => $users->hasMorePages(),
                'lastPage' => $users->lastPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'nextPageUrl' => $users->nextPageUrl(),
                'previousPageUrl' => $users->previousPageUrl(),
            ],
            'assignableRoles' => $assignableRoles,
            'organizationName' => $organization->name,
        ]);
    }

    /**
     * Create a new user in the organization.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $organization = app('organization');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(
                Role::whereNotIn('name', self::PROTECTED_ROLES)
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->toArray()
            )],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'organization_uuid' => $organization->uuid,
        ]);

        $user->assignRole($validated['role']);

        return redirect()->back()->with('toastNotifications', [[
            'title' => 'User Created',
            'message' => "{$user->name} has been added to the organization.",
            'variant' => 'default',
        ]]);
    }

    /**
     * Update an organization member.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(
                Role::whereNotIn('name', self::PROTECTED_ROLES)
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->toArray()
            )],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->back(status: 303)->with('toastNotifications', [[
            'title' => 'User Updated',
            'message' => "{$user->name} has been updated.",
            'variant' => 'default',
        ]]);
    }

    /**
     * Remove a user from the organization.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $name = $user->name;
        $user->delete();

        return redirect()->back()->with('toastNotifications', [[
            'title' => 'User Removed',
            'message' => "{$name} has been removed from the organization.",
            'variant' => 'default',
        ]]);
    }
}

