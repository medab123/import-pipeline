<?php

declare(strict_types=1);

namespace Elaitech\Import\Contracts\Services\DataMapper;

use Elaitech\Import\Services\DataMapper\DTO\DataMappingResultData;
use Elaitech\Import\Services\DataMapper\DTO\MappingConfigurationData;

interface DataMapperInterface
{
    public function map(MappingConfigurationData $config): DataMappingResultData;
}
