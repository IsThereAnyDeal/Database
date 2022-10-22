<?php
namespace IsThereAnyDeal\Database\TestObjects;

class PriceSerializer
{
    public static function serializePrice(Price $price) {
        return [
            $price->amount,
            $price->currency->code
        ];
    }

    public static function deserializePrice(object $row, array $names) {
        $amount = $row->{$names[0]};
        $currency = $row->{$names[1]};
        return new Price($amount, new Currency($currency));
    }
}
