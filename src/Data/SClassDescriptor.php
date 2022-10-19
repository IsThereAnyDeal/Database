<?php

namespace IsThereAnyDeal\Database\Data;

use IsThereAnyDeal\Database\Enums\EConstructionType;
use ReflectionClass;

class SClassDescriptor
{
    /**
     * @template T of object
     * @param ReflectionClass<T> $class
     * @param EConstructionType $construction
     * @param array<SColumnDescriptor> $columns
     */
    public function __construct(
        public readonly ReflectionClass $class,
        public readonly EConstructionType $construction,
        public readonly array $columns,
    ) {}
}
