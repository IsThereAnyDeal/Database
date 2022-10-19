<?php

namespace IsThereAnyDeal\Database\Sql\Data;

use IsThereAnyDeal\Database\Enums\EConstructionType;
use ReflectionClass;

class SClassDescriptor
{
    /**
     * @param ReflectionClass $class
     * @param EConstructionType $construction
     * @param array<SColumnDescriptor> $columns
     */
    public function __construct(
        public readonly ReflectionClass $class,
        public readonly EConstructionType $construction,
        public readonly array $columns,
    ) {}
}
