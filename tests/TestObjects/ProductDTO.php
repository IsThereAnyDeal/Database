<?php

namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\AInsertableObject;
use IsThereAnyDeal\Database\Attributes\Column;

class ProductDTO extends AInsertableObject
{
    private string $name;

    #[
        Column("price", serializer: [PriceSerializer::class, "serializePrice"]),
        Column("currency", serializer: [PriceSerializer::class, "serializeCurrency"])
    ]
    private Price $price;

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function getPrice(): Price {
        return $this->price;
    }

    public function setPrice(Price $price): self {
        $this->price = $price;
        return $this;
    }
}
