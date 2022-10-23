<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Column;

class MappedDTO
{
    #[Column("product_id")]
    public readonly int $id;
    public readonly string $title;
}
