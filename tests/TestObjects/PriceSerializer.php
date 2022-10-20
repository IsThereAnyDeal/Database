<?php
namespace IsThereAnyDeal\Database\TestObjects;

class PriceSerializer
{
    public function serializePrice(Price $price) {
        return $price->amount;
    }

    public function serializeCurrency(Price $price) {
        return $price->currency;
    }

    public static function deserializePrice(object $row, array $names) {
        $amount = $row->{$names[0]};
        $currency = $row->{$names[1]};
        return new Price($amount, new Currency($currency));
    }
}
