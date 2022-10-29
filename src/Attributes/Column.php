<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;
use BackedEnum;
use IsThereAnyDeal\Database\Exceptions\InvalidDeserializerException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param null|string|string[] $name
     * @param null|string|(callable(object): null|scalar|BackedEnum|list<null|scalar|BackedEnum>) $serializer
     * @param null|(callable(null|scalar ...): ?object) $deserializer
     * @throws InvalidDeserializerException
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {
        if (!is_null($this->deserializer) && !is_callable($this->deserializer)) {
            throw new InvalidDeserializerException("Deserializable is not callable");
        }
    }
}
