<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;
use IsThereAnyDeal\Database\Exceptions\InvalidDeserializerException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param null|string|string[] $name
     * @param null|callable $serializer
     * @param null|callable $deserializer
     * @throws InvalidDeserializerException
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {}
}
