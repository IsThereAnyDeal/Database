<?php
namespace IsThereAnyDeal\Database\TestObjects;

class Price
{
    public function __construct(
        public int $amount,
        public Currency $currency
    ) {}
}
