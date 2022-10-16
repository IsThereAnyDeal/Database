<?php
namespace IsThereAnyDeal\Database\Sql\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class TableColumn
{
    public function __construct(
        public readonly ?string $name=null
    ) {}
}
