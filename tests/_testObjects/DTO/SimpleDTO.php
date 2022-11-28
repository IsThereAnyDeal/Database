<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Tests\_testObjects\Values\Currency;

class SimpleDTO
{
    public readonly int $id;
    public readonly string $title;
    public readonly ?Currency $currency;
}
