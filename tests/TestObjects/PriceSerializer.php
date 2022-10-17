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
}
