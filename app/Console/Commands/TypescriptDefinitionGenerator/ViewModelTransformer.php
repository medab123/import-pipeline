<?php

declare(strict_types=1);

namespace App\Console\Commands\TypescriptDefinitionGenerator;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\Transformers\TransformsTypes;
use Spatie\TypeScriptTransformer\TypeProcessors\DtoCollectionTypeProcessor;
use Spatie\TypeScriptTransformer\TypeProcessors\ReplaceDefaultsTypeProcessor;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Spatie\ViewModels\ViewModel;

final class ViewModelTransformer implements Transformer
{
    use TransformsTypes;

    protected TypeScriptTransformerConfig $config;

    public function __construct(TypeScriptTransformerConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param  \ReflectionClass<\Spatie\ViewModels\ViewModel>  $class
     *
     * @throws \Spatie\TypeScriptTransformer\Exceptions\InvalidDefaultTypeReplacer
     */
    public function transform(ReflectionClass $class, string $name): ?TransformedType
    {
        if (! $class->isSubclassOf(ViewModel::class)) {
            return null;
        }

        $missingSymbols = new MissingSymbolsCollection;

        $type = implode([
            $this->transformMethods($class, $missingSymbols),
            $this->transformProperties($class, $missingSymbols),
        ]);

        return TransformedType::create(
            $class,
            $name,
            '{'.PHP_EOL.$type.'}',
            $missingSymbols,
        );
    }

    /**
     * @param  \ReflectionClass<\Spatie\ViewModels\ViewModel>  $class
     *
     * @throws \Spatie\TypeScriptTransformer\Exceptions\InvalidDefaultTypeReplacer
     */
    private function transformMethods(ReflectionClass $class, MissingSymbolsCollection $missingSymbols): string
    {
        return array_reduce(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            function (string $carry, ReflectionMethod $method) use ($class, $missingSymbols) {
                if (($method->class !== $class->name) || Str::startsWith($method->name, '__')) {
                    return $carry;
                }

                $returnType = 'any';

                if ($method->hasReturnType()) {
                    $returnType = $this->reflectionToTypeScript(
                        $method,
                        $missingSymbols,
                        ...$this->typeProcessors(),
                    );
                }

                // Spatie ViewModel transforms methods into properties, so generate as properties
                return "$carry{$method->getName()}: $returnType;".PHP_EOL;
            },
            '',
        );
    }

    /**
     * @return array<\Spatie\TypeScriptTransformer\TypeProcessors\TypeProcessor>
     *
     * @throws \Spatie\TypeScriptTransformer\Exceptions\InvalidDefaultTypeReplacer
     */
    private function typeProcessors(): array
    {
        return [
            true,
            new ReplaceDefaultsTypeProcessor(
                $this->config->getDefaultTypeReplacements(),
            ),
            new DtoCollectionTypeProcessor,
        ];
    }

    /**
     * @param  \ReflectionClass<\Spatie\ViewModels\ViewModel>  $class
     *
     * @throws \Spatie\TypeScriptTransformer\Exceptions\InvalidDefaultTypeReplacer
     */
    private function transformProperties(ReflectionClass $class, MissingSymbolsCollection $missingSymbols): string
    {
        return array_reduce(
            $class->getProperties(ReflectionProperty::IS_PUBLIC),
            function (string $carry, ReflectionProperty $property) use ($class, $missingSymbols) {
                if (($property->class !== $class->name)) {
                    return $carry;
                }

                $returnType = 'any';

                if ($property->hasType()) {
                    $returnType = $this->reflectionToTypeScript(
                        $property,
                        $missingSymbols,
                        ...$this->typeProcessors(),
                    );
                }

                return "$carry{$property->getName()}: $returnType;".PHP_EOL;
            },
            '',
        );
    }
}
