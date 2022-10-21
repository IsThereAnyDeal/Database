<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;
use IsThereAnyDeal\Database\Exceptions\InvalidDeserializerException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param null|string|string[] $name
     * @param callable $serializer
     * @param callable $deserializer
     * @throws InvalidDeserializerException
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {
        if (!is_null($this->deserializer) && !is_callable($this->deserializer)) {
            throw new \InvalidArgumentException("Deserializable is not callable");
        }

        if (is_array($this->name) && (is_null($this->deserializer))) {
            throw new InvalidDeserializerException();
        }
    }
}
