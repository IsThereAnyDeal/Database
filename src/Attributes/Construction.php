<?php
namespace IsThereAnyDeal\Database\Attributes;

use Attribute;
use IsThereAnyDeal\Database\Enums\EConstructionType;

#[Attribute(Attribute::TARGET_CLASS)]
class Construction
{
    public function __construct(
        public readonly EConstructionType $type
    ) {}
}
