<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Pipeline\Loaders;

use Elaitech\DataMapper\DTO\MappingConfigurationData;
use Elaitech\DataMapper\DTO\MappingRuleData;
use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\FilterConfigurationData;
use Elaitech\Import\Services\Core\DTOs\FilterRuleData;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineConfig;
use Elaitech\Import\Services\Pipeline\DTOs\ImportPipelineOptions;
use Elaitech\Import\Services\Pipeline\DTOs\ReaderConfig;
use Symfony\Component\Yaml\Yaml;

final class ImportPipelineYamlLoader
{
    public function loadFromFile(string $filePath): ImportPipelineConfig
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Configuration file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \InvalidArgumentException("Could not read configuration file: {$filePath}");
        }

        return $this->loadFromString($content);
    }

    public function loadFromString(string $yamlContent): ImportPipelineConfig
    {
        try {
            $data = Yaml::parse($yamlContent);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid YAML configuration: '.$e->getMessage());
        }

        if (! isset($data['pipeline'])) {
            throw new \InvalidArgumentException("Missing 'pipeline' section in configuration");
        }

        $pipeline = $data['pipeline'];

        return ImportPipelineConfig::from([
            'downloadRequest' => $this->buildDownloadRequest($pipeline['download'] ?? []),
            'readerConfig' => $this->buildReaderConfig($pipeline['read'] ?? []),
            'mappingConfig' => $this->buildMappingConfig($pipeline['map'] ?? []),
            'filterConfig' => isset($pipeline['filter']) ? $this->buildFilterConfig($pipeline['filter']) : null,
            'options' => $this->buildPipelineOptions($pipeline['options'] ?? []),
        ]);
    }

    private function buildDownloadRequest(array $config): DownloadRequestData
    {
        if (! isset($config['url'])) {
            throw new \InvalidArgumentException('Download URL is required');
        }

        $type = $config['type'] ?? 'https';
        $options = $config['options'] ?? [];

        return new DownloadRequestData(
            source: $config['url'],
            options: $options,
            preferredFilename: $config['filename'] ?? null
        );
    }

    private function buildReaderConfig(array $config): ReaderConfig
    {
        if (! isset($config['type'])) {
            throw new \InvalidArgumentException('Reader type is required');
        }

        return new ReaderConfig(
            type: $config['type'],
            options: $config['options'] ?? []
        );
    }

    private function buildMappingConfig(array $config): MappingConfigurationData
    {
        $rules = [];
        foreach ($config['rules'] ?? [] as $rule) {
            $rules[] = new MappingRuleData(
                sourceField: $rule['source_field'] ?? '',
                targetField: $rule['target_field'] ?? '',
                transformation: $rule['transformer'] ?? 'none',
                isRequired: $rule['required'] ?? false,
                defaultValue: $rule['default_value'] ?? null
            );
        }

        return new MappingConfigurationData(
            data: [], // Will be set during execution
            mappingRules: $rules,
            headers: $config['headers'] ?? []
        );
    }

    private function buildFilterConfig(array $config): FilterConfigurationData
    {
        $rules = [];
        foreach ($config['rules'] ?? [] as $rule) {
            $rules[] = new FilterRuleData(
                key: $rule['key'] ?? '',
                operator: $rule['operator'] ?? '',
                value: $rule['value'] ?? null,
                caseSensitive: $rule['case_sensitive'] ?? false,
                regexFlags: $rule['regex_flags'] ?? ''
            );
        }

        return new FilterConfigurationData(
            data: [], // Will be set during execution
            filterRules: $rules,
        );
    }

    private function buildPipelineOptions(array $config): ImportPipelineOptions
    {
        return new ImportPipelineOptions(
            enableCaching: $config['enable_caching'] ?? true,
            enableLogging: $config['enable_logging'] ?? true,
            stopOnError: $config['stop_on_error'] ?? false,
            cacheKey: $config['cache_key'] ?? null,
            timeout: $config['timeout'] ?? 300,
            enableMetrics: $config['enable_metrics'] ?? true
        );
    }

    /**
     * Validate YAML configuration against schema.
     */
    public function validateConfiguration(string $yamlContent): array
    {
        $errors = [];

        try {
            $data = Yaml::parse($yamlContent);
        } catch (\Exception $e) {
            return ['Invalid YAML: '.$e->getMessage()];
        }

        // Validate required sections
        if (! isset($data['pipeline'])) {
            $errors[] = "Missing required 'pipeline' section";

            return $errors;
        }

        $pipeline = $data['pipeline'];

        // Validate download section
        if (! isset($pipeline['download'])) {
            $errors[] = "Missing required 'download' section";
        } else {
            $download = $pipeline['download'];
            if (! isset($download['url'])) {
                $errors[] = "Missing required 'download.url'";
            }
            if (! isset($download['type'])) {
                $errors[] = "Missing required 'download.type'";
            } elseif (! in_array($download['type'], ['http', 'https', 'ftp', 'sftp'])) {
                $errors[] = "Invalid download type: {$download['type']}";
            }
        }

        // Validate read section
        if (! isset($pipeline['read'])) {
            $errors[] = "Missing required 'read' section";
        } else {
            $read = $pipeline['read'];
            if (! isset($read['type'])) {
                $errors[] = "Missing required 'read.type'";
            } elseif (! in_array($read['type'], ['csv', 'json', 'xml', 'yaml', 'text'])) {
                $errors[] = "Invalid reader type: {$read['type']}";
            }
        }

        // Validate map section
        if (! isset($pipeline['map'])) {
            $errors[] = "Missing required 'map' section";
        } else {
            $map = $pipeline['map'];
            if (! isset($map['rules']) || ! is_array($map['rules'])) {
                $errors[] = "Missing or invalid 'map.rules'";
            } else {
                foreach ($map['rules'] as $index => $rule) {
                    if (! isset($rule['source_field'])) {
                        $errors[] = "Missing 'source_field' in map rule {$index}";
                    }
                    if (! isset($rule['target_field'])) {
                        $errors[] = "Missing 'target_field' in map rule {$index}";
                    }
                }
            }
        }

        // Validate filter section (optional)
        if (isset($pipeline['filter'])) {
            $filter = $pipeline['filter'];
            if (isset($filter['rules']) && is_array($filter['rules'])) {
                foreach ($filter['rules'] as $index => $rule) {
                    if (! isset($rule['key'])) {
                        $errors[] = "Missing 'key' in filter rule {$index}";
                    }
                    if (! isset($rule['operator'])) {
                        $errors[] = "Missing 'operator' in filter rule {$index}";
                    }
                }
            }
            if (isset($filter['logic']) && ! in_array($filter['logic'], ['AND', 'OR'])) {
                $errors[] = "Invalid filter logic: {$filter['logic']}";
            }
        }

        return $errors;
    }
}
