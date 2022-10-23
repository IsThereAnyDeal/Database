<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Serializers;

use IsThereAnyDeal\Database\Tests\_testObjects\Values\Currency;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Price;

class PriceSerializer
{
    /** @return array{int, string} */
    public static function serializePrice(Price $price): array {
        return [
            $price->amount,
            $price->currency->code
        ];
    }

    public static function deserializePrice(int $amount, string $currency): Price {
        return new Price($amount, new Currency($currency));
    }
}
