<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Serializers;

use IsThereAnyDeal\Database\Tests\_testObjects\Enum\ESize;

class EnumSerializer
{
    public static function ESizeFromDbValue(string $value): ESize {
        return ESize::from($value);
    }
}
