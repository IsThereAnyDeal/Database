<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param callable $serializer
     * @param callable $deserializer
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {
        if (!is_null($this->deserializer) && !is_callable($this->deserializer)) {
            throw new \InvalidArgumentException("Deserializable is not callable");
        }
    }
}
