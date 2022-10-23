<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Serializers;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\ESize;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\EType;

class SimpleSerializedDTO
{
    #[Column("type", deserializer: [EType::class, "from"])]
    public EType $type;

    #[Column(deserializer: [EnumSerializer::class, "ESizeFromDbValue"])]
    public ESize $size;

    public function __construct() {
        if (!isset($this->size)) {
            $this->size = ESize::Size10;
        }
    }
}
