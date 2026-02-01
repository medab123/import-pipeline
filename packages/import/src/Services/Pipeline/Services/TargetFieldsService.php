<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Services;

use App\Models\Sectors\Categories\Vehicle\VehicleBaseModel;
use App\Models\Sectors\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use Str;

final class TargetFieldsService
{
    protected array $fieldsToExclude = [
        'id',
        'uuid',
        'productable_type',
        'productable_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'category_id',
        'company_id',
        'created_by',
        'updated_by',
        'categorizable_type',
        'categorizable_id',
    ];

    /**
     * Get all available target fields from product and vehicle models.
     */
    public function getTargetFields(): array
    {
        $fields = collect();

        // Get product fields
        $productFields = $this->getProductFields();
        $fields = $fields->merge($productFields);

        // Get vehicle category fields
        $vehicleFields = $this->getVehicleCategoryFields();
        $fields = $fields->merge($vehicleFields);

        $pricingFields = $this->getPricingFields();
        $fields = $fields->merge($pricingFields);

        $imageFields = $this->getImageFields();
        $fields = $fields->merge($imageFields);

        return $fields
            ->whereNotIn('field', $this->fieldsToExclude)
            ->values()
            ->toArray();
    }

    /**
     * Get fields from all vehicle category models.
     */
    private function getVehicleCategoryFields(): Collection
    {
        $fields = collect();

        // Get all vehicle category model classes
        $vehicleModelClasses = $this->getVehicleModelClasses();

        foreach ($vehicleModelClasses as $modelClass) {
            $modelFields = $this->getModelFields($modelClass);
            $fields = $fields->merge($modelFields);
        }

        return $fields;
    }

    /**
     * Get fields from product models.
     */
    private function getProductFields(): Collection
    {
        $fields = collect();

        // Get all product model classes
        $productModelClasses = $this->getProductModelClasses();

        foreach ($productModelClasses as $modelClass) {
            $modelFields = $this->getModelFields($modelClass);
            $fields = $fields->merge($modelFields);
        }

        return $fields;
    }

    /**
     * Get image-related fields.
     */
    private function getImageFields(): Collection
    {
        return collect([
            [
                'field' => 'images',
                'label' => 'Images',
                'category' => 'Media',
                'description' => 'Product images and media files',
                'type' => 'array',
            ],
        ]);
    }

    private function getPricingFields(): Collection
    {
        $priceTypes = config('price-types');
        $fields = [];
        foreach ($priceTypes as $priceType) {
            $fields[] = [
                'field' => $priceType['code'].'_price',
                'label' => ucfirst($priceType['code']).' Price',
                'category' => 'Pricing',
                'description' => "Product {$priceType['code']} Price",
                'type' => 'number',
            ];
        }

        return collect($fields);
    }

    /**
     * Get all vehicle model classes.
     */
    private function getVehicleModelClasses(): array
    {
        $vehicleModelsPath = app_path('Models/Sectors/Categories/Vehicle');
        $classes = [];

        if (File::exists($vehicleModelsPath)) {
            $files = File::allFiles($vehicleModelsPath);

            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $className = 'App\\Models\\Sectors\\Categories\\Vehicle\\'.$file->getFilenameWithoutExtension();

                    if (class_exists($className) && is_subclass_of($className, VehicleBaseModel::class)) {
                        $classes[] = $className;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Get all product model classes.
     */
    private function getProductModelClasses(): array
    {
        $productModelsPath = app_path('Models/Sectors/');
        $classes = ['App\\Models\\Product'];

        if (File::exists($productModelsPath)) {
            $files = File::allFiles($productModelsPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $className = 'App\\Models\\Sectors\\'.$file->getFilenameWithoutExtension();

                    if (class_exists($className) && is_subclass_of($className, Model::class)) {
                        $classes[] = $className;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Get fields from a specific model class.
     */
    private function getModelFields(string $modelClass): Collection
    {
        $fields = collect();

        try {
            $reflection = new ReflectionClass($modelClass);
            $model = $reflection->newInstanceWithoutConstructor();

            // Get fillable fields
            $fillableFields = $model->getFillable();

            // Get model name for better labeling
            $modelName = $reflection->getShortName();

            foreach ($fillableFields as $field) {
                // Skip timestamps and IDs
                if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $fields->push([
                    'field' => $field,
                    'label' => Str::headline($field),
                    'category' => str_replace('Detail', '', $modelName),
                    'description' => $this->getFieldDescription(Str::headline($field), Str::headline($modelName)),
                    'type' => $this->getFieldType($field, $model),
                    'model' => $modelName,
                ]);
            }

        } catch (\Exception $e) {
            // Log error but continue with other models
            \Illuminate\Support\Facades\Log::warning("Failed to get fields from model {$modelClass}: ".$e->getMessage());
        }

        return $fields;
    }

    /**
     * Get field description based on field name and model.
     */
    private function getFieldDescription(string $field, string $modelName): string
    {
        $descriptions = [
            'engine_displacement' => 'Engine displacement in cubic centimeters',
            'horsepower' => 'Engine horsepower rating',
            'engine_type' => 'Type of engine (e.g., 4-Stroke, 2-Stroke)',
            'transmission' => 'Transmission type (e.g., Automatic, Manual)',
            'drive_system' => 'Drive system configuration',
            'fuel_system' => 'Fuel system type (e.g., Fuel Injection, Carburetor)',
            'fuel_capacity' => 'Fuel tank capacity in gallons',
            'ground_clearance' => 'Ground clearance in inches',
            'towing_capacity' => 'Maximum towing capacity in pounds',
            'payload_capacity' => 'Maximum payload capacity in pounds',
            'wheelbase' => 'Wheelbase measurement in inches',
            'length' => 'Overall length in inches',
            'width' => 'Overall width in inches',
            'height' => 'Overall height in inches',
            'dry_weight' => 'Dry weight in pounds',
            'curb_weight' => 'Curb weight in pounds',
            'cargo_bed_capacity' => 'Cargo bed capacity in pounds',
            'cargo_bed_dimensions' => 'Cargo bed dimensions (L x W x H)',
            'winch_included' => 'Whether winch is included',
            'lighting' => 'Lighting system description',
            'steering' => 'Steering system type',
            'starting_system' => 'Starting system type',
            'differential_lock' => 'Differential lock availability',
            'eps' => 'Electric Power Steering availability',
            'instrumentation' => 'Instrumentation and display type',
            'colors_available' => 'Available color options',
            'drive_type' => 'Drive type (FWD, RWD, AWD, 4WD)',
            'fuel_type' => 'Fuel type (Gasoline, Diesel, Electric, etc.)',
            'engine' => 'Engine specification',
            'doors' => 'Number of doors',
            'seating_capacity' => 'Number of seats',
            'interior_color' => 'Interior color',
            'mileage_value' => 'Odometer reading',
            'mileage_unit' => 'Mileage unit (miles, kilometers)',
            'reports' => 'Vehicle history reports',
            'body_style' => 'Body style (Sedan, SUV, Truck, etc.)',
            'exterior_color' => 'Exterior paint color',
        ];

        return $descriptions[$field] ?? "{$modelName} {$field} field";
    }

    /**
     * Get field type based on field name and model casts.
     */
    private function getFieldType(string $field, Model $model): string
    {
        $casts = $model->getCasts();

        if (isset($casts[$field])) {
            $cast = $casts[$field];

            if (str_contains($cast, 'boolean')) {
                return 'boolean';
            } elseif (str_contains($cast, 'integer')) {
                return 'integer';
            } elseif (str_contains($cast, 'decimal') || str_contains($cast, 'float')) {
                return 'decimal';
            } elseif (str_contains($cast, 'array') || str_contains($cast, 'json')) {
                return 'array';
            } elseif (str_contains($cast, 'date')) {
                return 'date';
            }
        }

        // Default type based on field name patterns
        if (str_contains($field, 'capacity') || str_contains($field, 'weight') || str_contains($field, 'displacement')) {
            return 'decimal';
        } elseif (str_contains($field, 'included') || str_contains($field, 'lock') || str_contains($field, 'eps')) {
            return 'boolean';
        } elseif (str_contains($field, 'doors') || str_contains($field, 'seating')) {
            return 'integer';
        }

        return 'string';
    }
}
