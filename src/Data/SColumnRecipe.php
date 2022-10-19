<?php

namespace IsThereAnyDeal\Database\Data;

use ReflectionProperty;

class SColumnRecipe
{
    /**
     * @param ReflectionProperty $property
     * @param string|callable(object): mixed $setter
     */
    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly mixed $setter
    ) {}
}
