<?php

namespace IsThereAnyDeal\Database\Sql\Data;

use ReflectionProperty;

class SColumnRecipe
{
    /**
     * @param ReflectionProperty $property
     * @param callable(object): mixed $setter
     */
    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly mixed $setter
    ) {}
}