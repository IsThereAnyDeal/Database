<?php

namespace IsThereAnyDeal\Database\Sql\Data;

use ReflectionProperty;

class SColumnDescriptor
{
    /**
     * @param ReflectionProperty $property
     * @param array<string>|string $column
     * @param null|callable(mixed...): mixed $deserializer
     */
    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly array|string $column,
        public readonly mixed $deserializer = null
    ) {}
}
