<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\Organization;

use App\Http\Controllers\Controller;
use App\Models\TargetField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TargetFieldController extends Controller
{
    /**
     * Display listing of target fields.
     */
    public function index(Request $request): InertiaResponse
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
    public function create(): InertiaResponse
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
    public function show(TargetField $targetField): InertiaResponse
    {
        $this->ensureBelongsToOrganization($targetField);

        return Inertia::render('Dashboard/Organization/TargetFields/Show', [
            'targetField' => $targetField,
        ]);
    }

    /**
     * Show form for editing the specified target field.
     */
    public function edit(TargetField $targetField): InertiaResponse
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

    /**
     * Export target fields as CSV.
     */
    public function export(): StreamedResponse
    {
        $organization = app('organization');

        $targetFields = TargetField::where('organization_uuid', $organization->uuid)
            ->orderBy('category')
            ->orderBy('field')
            ->get();

        $fileName = sprintf(
            'target-fields-%s-%s.csv',
            Str::slug($organization->name ?: 'export', '_'),
            now()->format('Y-m-d_His')
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
        ];

        $callback = function () use ($targetFields) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($file, ['field', 'label', 'category', 'description', 'type', 'model']);

            // Write data rows
            foreach ($targetFields as $field) {
                fputcsv($file, [
                    $field->field,
                    $field->label,
                    $field->category ?? '',
                    $field->description ?? '',
                    $field->type,
                    $field->model ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import target fields from CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $organization = app('organization');

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ]);

        try {
            /** @var \Illuminate\Http\UploadedFile $file */
            $file = $validated['csv_file'];
            $path = $file->getRealPath();

            if ($path === false) {
                throw new \RuntimeException('Unable to read uploaded file.');
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open CSV file.');
            }

            // Skip UTF-8 BOM if present
            $firstBytes = fread($handle, 3);
            if ($firstBytes !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }

            // Read header row
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                throw new \RuntimeException('CSV file is empty or invalid.');
            }

            // Normalize headers (trim and lowercase)
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);

            // Validate required headers
            $requiredHeaders = ['field', 'label', 'type'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            if (!empty($missingHeaders)) {
                fclose($handle);
                throw new \RuntimeException(
                    'Missing required columns: '.implode(', ', $missingHeaders)
                );
            }

            $imported = 0;
            $updated = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                $rowNumber = 1; // Header is row 1

                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;

                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map row data to associative array
                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        $rowData[$header] = $row[$index] ?? '';
                    }

                    // Validate row data
                    $field = trim($rowData['field'] ?? '');
                    $label = trim($rowData['label'] ?? '');
                    $type = trim($rowData['type'] ?? '');

                    if (empty($field) || empty($label) || empty($type)) {
                        $errors[] = "Row {$rowNumber}: Missing required field (field, label, or type)";
                        continue;
                    }

                    // Validate type
                    $allowedTypes = ['string', 'integer', 'boolean', 'float', 'date', 'datetime'];
                    if (!in_array($type, $allowedTypes, true)) {
                        $errors[] = "Row {$rowNumber}: Invalid type '{$type}'. Allowed types: ".implode(', ', $allowedTypes);
                        continue;
                    }

                    // Prepare data
                    $data = [
                        'organization_uuid' => $organization->uuid,
                        'field' => $field,
                        'label' => $label,
                        'type' => $type,
                        'category' => !empty($rowData['category']) ? trim($rowData['category']) : null,
                        'description' => !empty($rowData['description']) ? trim($rowData['description']) : null,
                        'model' => !empty($rowData['model']) ? trim($rowData['model']) : null,
                    ];

                    // Update or create
                    $existing = TargetField::where('organization_uuid', $organization->uuid)
                        ->where('field', $field)
                        ->first();

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        TargetField::create($data);
                        $imported++;
                    }
                }

                fclose($handle);
                DB::commit();

                $message = sprintf(
                    'Successfully imported %d new field(s) and updated %d existing field(s).',
                    $imported,
                    $updated
                );

                if (!empty($errors)) {
                    $message .= ' '.count($errors).' error(s) occurred.';
                }

                return redirect()->route('dashboard.organization.target-fields.index')
                    ->with('toastNotifications', [[
                        'title' => $errors ? 'Import Completed with Errors' : 'Import Successful',
                        'message' => $message,
                        'variant' => $errors ? 'warning' : 'default',
                    ]]);
            } catch (\Throwable $e) {
                DB::rollBack();
                fclose($handle);
                throw $e;
            }
        } catch (\Throwable $e) {
            return redirect()->route('dashboard.organization.target-fields.index')
                ->with('toastNotifications', [[
                    'title' => 'Import Failed',
                    'message' => 'Failed to import CSV: '.$e->getMessage(),
                    'variant' => 'destructive',
                ]]);
        }
    }

    private function ensureBelongsToOrganization(TargetField $targetField): void
    {
        $organization = app('organization');
        if ($targetField->organization_uuid !== $organization->uuid) {
            abort(403, 'Unauthorized action.');
        }
    }
}
