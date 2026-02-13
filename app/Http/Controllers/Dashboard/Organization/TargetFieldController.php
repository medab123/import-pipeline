<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Organization;

use App\Http\Controllers\Controller;
use App\Models\TargetField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class TargetFieldController extends Controller
{
    /**
     * Display listing of target fields.
     */
    public function index(Request $request): Response
    {
        $organization = app('organization');
        
        $query = TargetField::where('organization_uuid', $organization->uuid);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('field', 'like', '%' . $request->search . '%')
                  ->orWhere('label', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        $targetFields = $query->orderBy('category')->orderBy('field')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Dashboard/Organization/TargetFields/Index', [
            'targetFields' => $targetFields,
            'filters' => [
                'search' => $request->search,
            ],
            'organizationName' => $organization->name,
        ]);
    }

    /**
     * Show form for creating a new target field.
     */
    public function create(): Response
    {
        return Inertia::render('Dashboard/Organization/TargetFields/Create');
    }

    /**
     * Store a newly created target field.
     */
    public function store(Request $request): RedirectResponse
    {
        $organization = app('organization');

        $validated = $request->validate([
            'field' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('target_fields')->where('organization_uuid', $organization->uuid)
            ],
            'label' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:string,integer,boolean,float,date,datetime'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        TargetField::create([
            ...$validated,
            'organization_uuid' => $organization->uuid,
        ]);

        return redirect()->route('dashboard.organization.target-fields.index')
            ->with('toastNotifications', [[
                'title' => 'Field Created',
                'message' => 'Target field created successfully.',
                'variant' => 'default',
            ]]);
    }

    /**
     * Display the specified target field.
     */
    public function show(TargetField $targetField): Response
    {
        $this->ensureBelongsToOrganization($targetField);

        return Inertia::render('Dashboard/Organization/TargetFields/Show', [
            'targetField' => $targetField,
        ]);
    }

    /**
     * Show form for editing the specified target field.
     */
    public function edit(TargetField $targetField): Response
    {
        $this->ensureBelongsToOrganization($targetField);

        return Inertia::render('Dashboard/Organization/TargetFields/Edit', [
            'targetField' => $targetField,
        ]);
    }

    /**
     * Update the specified target field.
     */
    public function update(Request $request, TargetField $targetField): RedirectResponse
    {
        $this->ensureBelongsToOrganization($targetField);
        $organization = app('organization');

        $validated = $request->validate([
            'field' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('target_fields')
                    ->where('organization_uuid', $organization->uuid)
                    ->ignore($targetField->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:string,integer,boolean,float,date,datetime'],
            'model' => ['nullable', 'string', 'max:255'],
        ]);

        $targetField->update($validated);

        return redirect()->route('dashboard.organization.target-fields.index')
            ->with('toastNotifications', [[
                'title' => 'Field Updated',
                'message' => 'Target field updated successfully.',
                'variant' => 'default',
            ]]);
    }

    /**
     * Remove the specified target field.
     */
    public function destroy(TargetField $targetField): RedirectResponse
    {
        $this->ensureBelongsToOrganization($targetField);

        $targetField->delete();

        return redirect()->route('dashboard.organization.target-fields.index')
            ->with('toastNotifications', [[
                'title' => 'Field Deleted',
                'message' => 'Target field deleted successfully.',
                'variant' => 'default',
            ]]);
    }

    private function ensureBelongsToOrganization(TargetField $targetField): void
    {
        $organization = app('organization');
        if ($targetField->organization_uuid !== $organization->uuid) {
            abort(403, 'Unauthorized action.');
        }
    }
}
