<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;
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
    public EType $type;

    #[Column(deserializer: [EnumSerializer::class, "ESizeFromDbValue"])]
    public ESize $size;

    public function __construct() {
        if (!isset($this->size)) {
            $this->size = ESize::Size10;
        }
    }
}

class PriceSerializer {
    public static function fromDbValue(object $row, array $names) {
        $amount = $row->{$names[0]};
        $code = $row->{$names[1]};

        $currency = new Currency($code);
        return new Price($amount, $currency);
    }
}

#[Construction(EConstructionType::AfterFetch)]
class ComplexSerializedDTO {
    #[Column(["price", "currency"],
        deserializer: [PriceSerializer::class, "fromDbValue"])]
    public Price $price;

    #[Column("price")]
    public int $priceAmount;

    #[Column(["sale", "currency"],
        deserializer: [PriceSerializer::class, "fromDbValue"])]
    public Price $sale;

    public readonly int $cut;

    public function __construct(?int $customRate = null, ?Currency $currency=null) {
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

        if (isset($this->price) && isset($this->sale)) {
            $this->cut = 100 - round($this->sale->amount / ($this->price->amount/100));
        }
    }
}

#[Construction(EConstructionType::None)]
class NoConstructorDTO {

    public int $id;
    public ?int $time = null;

    public function __construct() {
        $this->time = time();
    }
}

#[Construction(EConstructionType::BeforeFetch)]
class PreFetchConstructorDTO {

    public int $id;
    public ?int $time = null;

    public function __construct() {
        $this->time = time();
    }
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

    public function testPreFetchConstructor(): void {
        $data = [
            (object)["id" => 3348, "time" => null]
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(PreFetchConstructorDTO::class, $data);

        $item = $items->current();
        $this->assertEquals(3348, $item->id);
        $this->assertNull($item->time);
    }

    public function testPostFetchConstructor(): void {

        $data = [
            (object)["price" => 4000, "sale" => 1000, "currency" => "USD"],
            (object)["price" => 5000, "sale" =>  500, "currency" => "USD"],
        ];

        $expected = [
            75,
            90
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertEquals($expected[$i], $item->cut);
            ++$i;
        }
    }

    public function testNoConstructor(): void {

        $data = [
            (object)["id" => 138]
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(NoConstructorDTO::class, $data);

        $item = $items->current();
        $this->assertEquals(138, $item->id);
        $this->assertNull($item->time);
    }

    public function testDefaultConstructor(): void {

        $data = [
            (object)["type" => 2],
            (object)["type" => 1],
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(SimpleSerializedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(SimpleSerializedDTO::class, $item);
            $this->assertEquals(EType::from($data[$i]->type), $item->type);
            $this->assertEquals(ESize::Size10, $item->size);
            ++$i;
        }
    }

    public function testConstructorParams(): void {

        $data = [
            (object)["sale" => 1000, "currency" => "USD"],
        ];

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data, 10);

        $item = $items->current();
        $this->assertEquals(10000, $item->sale->amount);
        $this->assertEquals("USD", $item->sale->currency->code);

        $items = $builder->build(ComplexSerializedDTO::class, $data, 2, new Currency("EUR"));

        $item = $items->current();
        $this->assertEquals(2000, $item->sale->amount);
        $this->assertEquals("EUR", $item->sale->currency->code);
    }
}
