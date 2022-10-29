<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Currency;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Price;
use IsThereAnyDeal\Database\Tests\_testObjects\Serializers\PriceSerializer;

#[Construction(EConstructionType::AfterFetch)]
class ComplexSerializedDTO
{
    #[Column(["price", "currency"], deserializer: [PriceSerializer::class, "deserializePrice"])]
    public Price $price;

    #[Column("price")]
    public int $priceAmount;

    #[Column(["sale", "currency"], deserializer: [PriceSerializer::class, "deserializePrice"])]
    public ?Price $sale;

    public ?string $title;

    public function __construct(?int $customRate = null, ?Currency $currency = null) {
        if (!is_null($customRate)) {
            if (isset($this->price)) {
                $this->price->amount *= $customRate;
            }

            if (isset($this->priceAmount)) {
                $this->priceAmount *= $customRate;
            }

            if (isset($this->sale)) {
                $this->sale->amount *= $customRate;
            }
        }

        if (!is_null($currency)) {
            if (isset($this->price)) {
                $this->price->currency = $currency;
            }
            if (isset($this->sale)) {
                $this->sale->currency = $currency;
            }
        }
    }
}
