<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\DataMapper\Contracts;

use Elaitech\Import\Services\DataMapper\DTO\DataMappingResultData;
use Elaitech\Import\Services\DataMapper\DTO\MappingConfigurationData;

interface DataMapperInterface
{
    public function map(MappingConfigurationData $config): DataMappingResultData;
}
