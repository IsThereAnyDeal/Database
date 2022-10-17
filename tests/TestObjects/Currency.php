<?php
namespace IsThereAnyDeal\Database\TestObjects;

class Currency
{
    public function __construct(
        public readonly string $code
    ) {}
}
