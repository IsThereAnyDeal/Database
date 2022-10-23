<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Serializers;

use IsThereAnyDeal\Database\Tests\_testObjects\Values\Currency;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Price;

class PriceSerializer
{
    public static function serializePrice(Price $price) {
        return [
            $price->amount,
            $price->currency->code
        ];
    }

    public static function deserializePrice(int $amount, string $currency) {
        return new Price($amount, new Currency($currency));
    }
}
