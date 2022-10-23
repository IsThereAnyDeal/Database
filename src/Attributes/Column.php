<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;
use BackedEnum;
use IsThereAnyDeal\Database\Exceptions\InvalidDeserializerException;
use IsThereAnyDeal\Database\Exceptions\InvalidSerializerException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param null|string|string[] $name
     * @param null|string|(callable(object): null|scalar|BackedEnum|list<null|scalar|BackedEnum>) $serializer
     * @param null|(callable(null|scalar ...): object) $deserializer
     * @throws InvalidDeserializerException
     * @throws InvalidSerializerException
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {
        if (!is_null($this->deserializer) && !is_callable($this->deserializer)) {
            throw new InvalidDeserializerException("Deserializable is not callable");
        }

        if (!is_null($this->serializer)
            && !is_callable($this->serializer)
            && (!is_string($this->serializer) || $this->serializer[0] != "@")
        ) {
            throw new InvalidSerializerException();
        }
    }
}
