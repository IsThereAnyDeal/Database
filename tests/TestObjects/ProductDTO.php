<?php

namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\AInsertableObject;
use IsThereAnyDeal\Database\Sql\Attributes\ColumnSetup;

class ProductDTO extends AInsertableObject
{
    private string $name;

    #[
        ColumnSetup("price", serializer: [PriceSerializer::class, "serializePrice"]),
        ColumnSetup("currency", serializer: [PriceSerializer::class, "serializeCurrency"])
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
