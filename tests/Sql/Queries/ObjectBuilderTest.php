<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Sql\Data\ObjectBuilder;
use IsThereAnyDeal\Database\TestObjects\Currency;
use IsThereAnyDeal\Database\TestObjects\Price;
use PHPUnit\Framework\TestCase;

class SimpleDTO {
    public readonly int $id;
    public readonly string $title;
}

class MappedDTO {
    #[Column("product_id")]
    public readonly int $id;
    public readonly string $title;
}

enum EType: int {
    case TypeA = 1;
    case TypeB = 2;
}

enum ESize: string {
    case Size10 = "10";
    case Size20 = "20";
}

class EnumSerializer {
    public static function ESizeFromDbValue(string $value): ESize {
        return ESize::from($value);
    }
}

class SimpleSerializedDTO {
    #[Column("type", deserializer: [EType::class, "from"])]
    public readonly EType $type;

    #[Column(deserializer: [EnumSerializer::class, "ESizeFromDbValue"])]
    public readonly ESize $size;
}

class PriceSerializer {
    public static function fromDbValue(object $row, array $names) {
        $amount = $row->{$names[0]};
        $code = $row->{$names[1]};

        $currency = new Currency($code);
        return new Price($amount, $currency);
    }
}

class ComplexSerializedDTO {
    #[Column(["price", "currency"],
        deserializer: [PriceSerializer::class, "fromDbValue"])]
    public readonly Price $price;

    #[Column("price")]
    public readonly int $priceAmount;

    #[Column(["sale", "currency"],
        deserializer: [PriceSerializer::class, "fromDbValue"])]
    public readonly Price $sale;
}

class ObjectBuilderTest extends TestCase
{

    public function testSimpleBuild(): void {

        $data = [
            (object)["id" => 1, "title" => "Sample Title"],
            (object)["id" => 3, "title" => "Second Title"]
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(SimpleDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(SimpleDTO::class, $item);
            $this->assertEquals($data[$i]->id, $item->id);
            $this->assertEquals($data[$i]->title, $item->title);
            ++$i;
        }
    }

    public function testColumnMapping(): void {

        $data = [
            (object)["product_id" => 100, "title" => "First"],
            (object)["product_id" => 123, "title" => "Second"],
            (object)["product_id" => 3843, "title" => "Third"],
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(MappedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(MappedDTO::class, $item);
            $this->assertEquals($data[$i]->product_id, $item->id);
            $this->assertEquals($data[$i]->title, $item->title);
            ++$i;
        }
    }


    public function testSimpleDeserializetion(): void {

        $data = [
            (object)["type" => 2, "size" => "10"],
            (object)["type" => 1, "size" => "20"],
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(SimpleSerializedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(SimpleSerializedDTO::class, $item);
            $this->assertEquals(EType::from($data[$i]->type), $item->type);
            $this->assertEquals(ESize::from($data[$i]->size), $item->size);
            ++$i;
        }
    }

    public function testComplexDeserializetion(): void {

        $data = [
            (object)["price" => 1999, "currency" => "USD"],
            (object)["price" => 2795, "currency" => "EUR"],
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(ComplexSerializedDTO::class, $item);
            $this->assertEquals($data[$i]->price, $item->priceAmount);
            $this->assertEquals($data[$i]->price, $item->price->amount);
            $this->assertEquals($data[$i]->currency, $item->price->currency->code);
            $this->assertFalse(isset($item->sale));
            ++$i;
        }
    }
}
