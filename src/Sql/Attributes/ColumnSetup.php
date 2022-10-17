<?php
namespace IsThereAnyDeal\Database\Sql\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ColumnSetup
{
    /**
     * @param callable $serializer
     * @param callable $deserializer
     */
    public function __construct(
        public readonly string $name,
        public readonly mixed $serializer=null,
        public readonly mixed $deserializer=null
    ) {}
}
