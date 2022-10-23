<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Tests\_testObjects\Serializers\PriceSerializer;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Price;

class ProductDTO
{
    private string $name;

    #[Column(
        ["price", "currency"],
        deserializer: [PriceSerializer::class, "deserializePrice"],
        serializer: [PriceSerializer::class, "serializePrice"],
    )]
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
