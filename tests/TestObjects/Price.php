<?php
namespace IsThereAnyDeal\Database\TestObjects;

class Price
{
    public function __construct(
        public readonly int $amount,
        public readonly Currency $currency
    ) {}
}
