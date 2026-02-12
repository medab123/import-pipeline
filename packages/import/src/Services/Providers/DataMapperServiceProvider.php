<?php

declare(strict_types=1);

namespace Elaitech\Providers;

use Elaitech\DataMapper\Contracts\DataMapperInterface;
use Elaitech\DataMapper\DataMapperService;
use Elaitech\DataMapper\FieldExtractor;
use Elaitech\DataMapper\Transformers\IntegerTransformer;
use Elaitech\DataMapper\Transformers\LowerTransformer;
use Elaitech\DataMapper\Transformers\NoneTransformer;
use Elaitech\DataMapper\Transformers\TrimTransformer;
use Elaitech\DataMapper\Transformers\UpperTransformer;
use Elaitech\DataMapper\Transformers\FloatTransformer;
use Elaitech\DataMapper\Transformers\BooleanTransformer;
use Elaitech\DataMapper\Transformers\DateTransformer;
use Elaitech\DataMapper\Transformers\ArrayFirstTransformer;
use Elaitech\DataMapper\Transformers\ArrayJoinTransformer;
use Elaitech\DataMapper\ValueTransformer;
use Illuminate\Support\ServiceProvider;

class DataMapperServiceProvider extends ServiceProvider
{
    private array $transformers = [
        NoneTransformer::class,
        TrimTransformer::class,
        UpperTransformer::class,
        LowerTransformer::class,
        IntegerTransformer::class,
        FloatTransformer::class,
        BooleanTransformer::class,
        DateTransformer::class,
        ArrayFirstTransformer::class,
        ArrayJoinTransformer::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the FieldExtractor as singleton
        $this->app->singleton(FieldExtractor::class);

        // Register the ValueTransformer as singleton and configure transformers
        $this->app->singleton(ValueTransformer::class, function ($app) {
            $valueTransformer = new ValueTransformer;
            // Register all built-in transformers
            $this->registerTransformers($valueTransformer);

            return $valueTransformer;
        });

        // Register the main DataMapperService
        $this->app->bind(DataMapperInterface::class, DataMapperService::class);
        $this->app->bind(DataMapperService::class, function ($app) {
            return new DataMapperService(
                $app->make(ValueTransformer::class),
                $app->make(FieldExtractor::class)
            );
        });
    }

    /**
     * Register all built-in transformers
     */
    private function registerTransformers(ValueTransformer $valueTransformer): void
    {
        foreach ($this->transformers as $transformerClass) {
            $valueTransformer->registerTransformer(new $transformerClass);
        }
    }
}
