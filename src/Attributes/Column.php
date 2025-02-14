<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * @param null|string|string[] $name
     * @param null|callable|array{class-string, string} $serializer
     * @param null|callable|array{class-string, string} $deserializer
     */
    public function __construct(
        public readonly null|array|string $name=null,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {}
}
