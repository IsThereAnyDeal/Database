<?php

namespace IsThereAnyDeal\Database\Data\Structs;

use ReflectionProperty;

class SColumnRecipe
{
    /**
     * @param ReflectionProperty $property
     * @param string|callable(null|scalar ...): object $setter
     */
    public function __construct(
        public readonly ReflectionProperty $property,
        public readonly mixed $setter
    ) {}
}
