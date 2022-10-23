<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Values;

class Price
{
    public function __construct(
        public int $amount,
        public Currency $currency
    ) {}
}
