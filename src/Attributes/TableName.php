<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TableName
{
    public function __construct(
        public readonly string $name
    ) {}
}
