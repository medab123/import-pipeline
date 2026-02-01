<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Prepare\Factories;

use Elaitech\Import\Services\Core\Abstracts\BaseFactory;
use Elaitech\Import\Services\Core\Exceptions\FactoryException;
use Elaitech\Import\Services\Prepare\Contracts\ResolverFactoryInterface;
use Elaitech\Import\Services\Prepare\Contracts\ResolverInterface;
use Elaitech\Import\Services\Prepare\Contracts\ResolverRegistryInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Resolver Factory
 *
 * Factory for creating resolver instances based on transformation names.
 * Follows the Import service factory pattern by extending BaseFactory.
 * Supports dynamic registration through ResolverRegistry.
 */
final class ResolverFactory extends BaseFactory implements ResolverFactoryInterface
{
    public function __construct(
        protected Container $container,
        private readonly ?ResolverRegistryInterface $registry = null
    ) {
        parent::__construct($container);
    }

    /**
     * Validate that the resolver class implements ResolverInterface.
     *
     * @param  string  $className  The resolver class name
     *
     * @throws FactoryException When a class doesn't implement ResolverInterface
     */
    protected function validateServiceClass(string $className): void
    {
        if (! is_subclass_of($className, ResolverInterface::class)) {
            throw FactoryException::invalidServiceClass(
                $className,
                ResolverInterface::class
            );
        }
    }

    /**
     * Get the expected interface for resolvers.
     *
     * @return string The ResolverInterface class name
     */
    protected function getExpectedInterface(): string
    {
        return ResolverInterface::class;
    }

    /**
     * Get a resolver instance for the given transformation name.
     * Checks the registry first, then falls back to registered mappings.
     *
     * @param  string  $type  The transformation name
     * @return ResolverInterface The resolver instance
     *
     * @throws FactoryException
     */
    public function for(string $type): ResolverInterface
    {
        if ($this->registry && $this->registry->has($type)) {
            return $this->registry->get($type);
        }

        // todo (make the parent factory use the registry) Fall back to registered mappings
        return parent::for($type);
    }

    /**
     * Check if a transformation is supported.
     * Checks both registry and registered mappings.
     *
     * @param  string  $type  The transformation name
     * @return bool True if supported, false otherwise
     */
    public function supports(string $type): bool
    {
        if ($this->registry && $this->registry->has($type)) {
            return true;
        }

        // todo (make the parent factory use the registry)
        return parent::supports($type);
    }

    /**
     * Get all available transformation names.
     * Includes both registry and registered mappings.
     *
     * @return array<string> Array of transformation names
     */
    public function getAvailableTypes(): array
    {
        $types = parent::getAvailableTypes();

        if ($this->registry) {
            $registryTypes = $this->registry->all();
            $types = array_unique(array_merge($types, $registryTypes));
        }

        // todo (make the parent factory use the registry)
        return $types;
    }
}
