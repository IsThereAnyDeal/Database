<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Values;

class Currency
{
    public function __construct(
        public readonly string $code
    ) {}
}
