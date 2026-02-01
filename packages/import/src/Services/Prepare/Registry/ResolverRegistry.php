<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Registry;

use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Elaitech\Import\Services\Prepare\Contracts\ResolverRegistryInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Resolver Registry
 *
 * Registry for dynamically registering and retrieving resolvers
 * for transformation names.
 */
final class ResolverRegistry implements ResolverRegistryInterface
{
    /**
     * Registered resolvers.
     *
     * @var array<string, class-string<ResolverInterface>|ResolverInterface>
     */
    private array $resolvers = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Register a resolver for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @param  class-string<ResolverInterface>|ResolverInterface  $resolver  The resolver class or instance
     *
     * @throws \InvalidArgumentException When resolver is invalid
     */
    public function register(string $transformationName, string|ResolverInterface $resolver): void
    {
        if (is_string($resolver)) {
            if (! class_exists($resolver)) {
                throw new \InvalidArgumentException(
                    "Resolver class '{$resolver}' does not exist"
                );
            }

            if (! is_subclass_of($resolver, ResolverInterface::class)) {
                throw new \InvalidArgumentException(
                    "Resolver class '{$resolver}' must implement ResolverInterface"
                );
            }
        } elseif (! $resolver instanceof ResolverInterface) {
            throw new \InvalidArgumentException(
                'Resolver must be a class string or ResolverInterface instance'
            );
        }

        $this->resolvers[$transformationName] = $resolver;
    }

    /**
     * Get a resolver for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @return ResolverInterface The resolver instance
     *
     * @throws \InvalidArgumentException|\Illuminate\Contracts\Container\BindingResolutionException When resolver is not found
     */
    public function get(string $transformationName): ResolverInterface
    {
        if (! $this->has($transformationName)) {
            throw new \InvalidArgumentException(
                "Resolver for transformation '{$transformationName}' is not registered. ".
                'Available transformations: '.implode(', ', array_keys($this->resolvers))
            );
        }

        $resolver = $this->resolvers[$transformationName];

        // If it's a class string, resolve it from the container
        if (is_string($resolver)) {
            $resolver = $this->container->make($resolver);

            if (! $resolver instanceof ResolverInterface) {
                throw new \RuntimeException(
                    "Resolved class '{$this->resolvers[$transformationName]}' does not implement ResolverInterface"
                );
            }

            // Cache the resolved instance
            $this->resolvers[$transformationName] = $resolver;
        }

        return $resolver;
    }

    /**
     * Check if a resolver is registered for a transformation name.
     *
     * @param  string  $transformationName  The transformation name
     * @return bool True if registered, false otherwise
     */
    public function has(string $transformationName): bool
    {
        return isset($this->resolvers[$transformationName]);
    }

    /**
     * Get all registered transformation names.
     *
     * @return array<string> Array of transformation names
     */
    public function all(): array
    {
        return array_keys($this->resolvers);
    }

    /**
     * Remove a resolver registration.
     *
     * @param  string  $transformationName  The transformation name
     */
    public function remove(string $transformationName): void
    {
        unset($this->resolvers[$transformationName]);
    }

    /**
     * Clear all resolver registrations.
     */
    public function clear(): void
    {
        $this->resolvers = [];
    }
}
