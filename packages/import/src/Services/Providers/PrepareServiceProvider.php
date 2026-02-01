<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Providers;

use Elaitech\Import\Services\Prepare\Contracts\ResolverFactoryInterface;
use Elaitech\Import\Services\Prepare\Factories\ResolverFactory;

/**
 * Prepare Service Provider
 *
 * Registers resolver services, registry, and factory for the prepare stage.
 * Supports dynamic registration of resolvers through the registry.
 */
final class PrepareServiceProvider extends BaseImportServiceProvider
{
    /**
     * Get the service mappings for resolvers.
     * Reads from config file for auto-registration.
     *
     * @return array<string, class-string> Mapping of transformation names to resolver classes
     */
    protected function getServiceMappings(): array
    {
        $resolvers = config('import-pipelines.resolvers', []);
        $mappings = [];

        foreach ($resolvers as $transformationName => $resolverConfig) {
            if (isset($resolverConfig['resolver'])) {
                $mappings[$transformationName] = $resolverConfig['resolver'];
            }
        }

        return $mappings;
    }

    /**
     * Get the factory class for resolvers.
     *
     * @return string The ResolverFactory class name
     */
    protected function getFactoryClass(): string
    {
        return ResolverFactory::class;
    }

    /**
     * Get the factory interface for resolvers.
     *
     * @return string The ResolverFactoryInterface class name
     */
    protected function getFactoryInterface(): string
    {
        return ResolverFactoryInterface::class;
    }

    /**
     * Get the service type name for this provider.
     *
     * @return string The service type name
     */
    protected function getServiceType(): string
    {
        return 'resolver';
    }

    /**
     * Register the factory with registry injection.
     * Override to inject the registry into the factory.
     */
    protected function registerFactory(): void
    {
        $this->app->singleton($this->getFactoryInterface(), function () {
            $factoryClass = $this->getFactoryClass();

            return resolve($factoryClass)->register($this->getServiceMappings());
        });
    }
}
