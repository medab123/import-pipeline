<?php

declare(strict_types=1);

return [
    'queues' => [
        'default' => env('IMPORT_PIPELINES_QUEUE', 'import-pipelines'),
        'high_priority' => env('IMPORT_PIPELINES_HIGH_PRIORITY_QUEUE', 'import-pipelines-high'),
        'low_priority' => env('IMPORT_PIPELINES_LOW_PRIORITY_QUEUE', 'import-pipelines-low'),
    ],

    'timeouts' => [
        'default' => env('IMPORT_PIPELINES_TIMEOUT', 5600),
        'large_files' => env('IMPORT_PIPELINES_LARGE_FILE_TIMEOUT', 7200),
        'small_files' => env('IMPORT_PIPELINES_SMALL_FILE_TIMEOUT', 1800),
    ],

    'retry' => [
        'max_attempts' => env('IMPORT_PIPELINES_RETRY_ATTEMPTS', 1),
        'backoff' => env('IMPORT_PIPELINES_BACKOFF', 60),
        'max_exceptions' => env('IMPORT_PIPELINES_MAX_EXCEPTIONS', 3),
    ],

    'memory' => [
        'default' => env('IMPORT_PIPELINES_MEMORY', 512),
        'large_files' => env('IMPORT_PIPELINES_LARGE_FILE_MEMORY', 1024),
    ],

    'scheduling' => [
        'tolerance_minutes' => env('IMPORT_PIPELINES_TOLERANCE_MINUTES', 5),
        'custom_interval_hours' => env('IMPORT_PIPELINES_CUSTOM_INTERVAL_HOURS', 24),
    ],

    'logging' => [
        'level' => env('IMPORT_PIPELINES_LOG_LEVEL', 'info'),
        'channels' => [
            'execution' => env('IMPORT_PIPELINES_EXECUTION_LOG_CHANNEL', 'single'),
            'scheduling' => env('IMPORT_PIPELINES_SCHEDULING_LOG_CHANNEL', 'single'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Resolvers Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the resolvers available for the prepare stage
    | of the import pipeline. Each resolver can be configured with its specific
    | settings.
    |
    */

    'resolvers' => [
        'category' => [
            'resolver' => \App\Services\Import\Prepare\Implementations\CategoryResolver::class,
            'config' => [
                'field' => 'category',
                'match_by' => 'slug',
            ],
        ],
        'generate_stock_id_from_vin' => [
            'resolver' => \App\Services\Import\Prepare\Implementations\StockIdResolver::class,
            'config' => [],
        ],
        'generate_vin_from_stock_id' => [
            'resolver' => \App\Services\Import\Prepare\Implementations\VinResolver::class,
            'config' => [],
        ],
        'pricing' => [
            'resolver' => \App\Services\Import\Prepare\Implementations\PricingResolver::class,
        ],
        'title' => [
            'resolver' => \App\Services\Import\Prepare\Implementations\TitleResolver::class,
        ],
    ],
];
