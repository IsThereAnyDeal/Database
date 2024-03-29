<?php
namespace IsThereAnyDeal\Database\Tests\Data;

use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Data\ObjectBuilder;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ChildDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ComplexSerializedDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ConstructorBaseDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ConstructorNoneDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ConstructorPostFetchDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\ConstructorPreFetchDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\MappedDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\DTO\SimpleDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\ESize;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\EString;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\EType;
use IsThereAnyDeal\Database\Tests\_testObjects\Serializers\SimpleSerializedDTO;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\Currency;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\SerializableList;
use IsThereAnyDeal\Database\Tests\_testObjects\Values\StaticConstructible;
use PHPUnit\Framework\TestCase;

class ObjectBuilderTest extends TestCase
{
    public function testNoData(): void {

        $data = new \EmptyIterator();

        $builder = new ObjectBuilder();
        $items = $builder->build(SimpleDTO::class, $data);

        $this->assertEquals(0, iterator_count($items));
    }

    public function testSimpleBuild(): void {

        $data = new \ArrayIterator([
            (object)["id" => 1, "title" => "Sample Title"],
            (object)["id" => 3, "title" => "Second Title"]
        ]);

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

        $data = new \ArrayIterator([
            (object)["product_id" => 100, "title" => "First"],
            (object)["product_id" => 123, "title" => "Second"],
            (object)["product_id" => 3843, "title" => "Third"],
        ]);

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

        $data = new \ArrayIterator([
            (object)["type" => 2, "size" => "10"],
            (object)["type" => 1, "size" => "20"],
        ]);

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

        $data = new \ArrayIterator([
            (object)["price" => 1999, "currency" => "USD", "enum" => "A", "nullableEnum" => 10],
            (object)["price" => 2795, "currency" => "EUR", "enum" => "C", "nullableEnum" => null],
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data);

        $item = $items->current();
        $this->assertInstanceOf(ComplexSerializedDTO::class, $item);
        $this->assertEquals(1999, $item->priceAmount);
        $this->assertEquals(1999, $item->price->amount);
        $this->assertEquals("USD", $item->price->currency->code);
        $this->assertFalse(isset($item->sale));
        $this->assertEquals(EString::ValueA, $item->enum);
        $this->assertEquals(ESize::Size10, $item->nullableEnum);;
        $items->next();

        $item = $items->current();
        $this->assertEquals(2795, $item->priceAmount);
        $this->assertEquals(2795, $item->price->amount);
        $this->assertEquals("EUR", $item->price->currency->code);
        $this->assertFalse(isset($item->sale));
        $this->assertEquals(EString::ValueC, $item->enum);
        $this->assertEquals(null, $item->nullableEnum);;
    }

    public function testPreFetchConstructor(): void {
        $data = new \ArrayIterator([
            (object)["id" => 3348]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ConstructorPreFetchDTO::class, $data);

        /** @var ConstructorBaseDTO $item */
        $item = $items->current();
        $this->assertEquals(3348, $item->id);
        $this->assertEquals("Constructor called before setting ID", $item->constructorValue);
    }

    public function testPostFetchConstructor(): void {
        $data = new \ArrayIterator([
            (object)["id" => 3348]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ConstructorPostFetchDTO::class, $data);

        /** @var ConstructorBaseDTO $item */
        $item = $items->current();
        $this->assertEquals(3348, $item->id);
        $this->assertEquals("Constructor called, ID: 3348", $item->constructorValue);
    }

    public function testNoConstructor(): void {
        $data = new \ArrayIterator([
            (object)["id" => 3348]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ConstructorNoneDTO::class, $data);

        /** @var ConstructorBaseDTO $item */
        $item = $items->current();
        $this->assertEquals(3348, $item->id);
        $this->assertEquals("Constructor was not called", $item->constructorValue);
    }

    public function testDefaultConstructor(): void {

        $data = new \ArrayIterator([
            (object)["type" => 2],
            (object)["type" => 1],
        ]);

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

        $data = new \ArrayIterator([
            (object)["sale" => 1000, "currency" => "USD"],
        ]);

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

    public function testAnonymousClassConstruction(): void {

        $data = new \ArrayIterator([
            (object)["product_id" => 1, "size" => "10"],
            (object)["product_id" => 3, "size" => "20"]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(new class {
            #[Column("product_id")]
            public int $id;

            #[Column(deserializer: [ESize::class, "from"])]
            public ESize $size;
        }, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertEquals($data[$i]->product_id, $item->id);
            $this->assertEquals(ESize::from($data[$i]->size), $item->size);
            ++$i;
        }
    }

    public function testNullDeserialization(): void {

        $data = new \ArrayIterator([
            (object)["price" => 2999, "currency" => "EUR", "sale" => null, "title" => null],
            (object)["price" => 2999, "currency" => "EUR", "sale" => 1499, "title" => "Test Title"],
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data);

        $i = 0;
        foreach($items as $item) {
            $this->assertInstanceOf(ComplexSerializedDTO::class, $item);
            $this->assertEquals($data[$i]->price, $item->price->amount);
            $this->assertEquals($data[$i]->currency, $item->price->currency->code);
            $this->assertEquals($data[$i]->sale, $item->sale?->amount);
            $this->assertEquals($data[$i]->title, $item->title);
            ++$i;
        }
    }

    public function testInstanceMethodDeserialization(): void {
        $data = new \ArrayIterator([
            (object)["list" => "1,3,5,9"],
            (object)["list" => null],
            (object)["list" => ""]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(new class{
            #[Column(deserializer: [SerializableList::class, "fromString"])]
            public ?SerializableList $list;
        }, $data);

        $item = $items->current();
        $this->assertEquals([1, 3, 5, 9], $item->list?->getValues());
        $items->next();

        $item = $items->current();
        $this->assertEquals(null, $item->list?->getValues());
        $items->next();

        $item = $items->current();
        $this->assertEquals([], $item->list?->getValues());
    }

    public function testInheritance(): void {
        $data = new \ArrayIterator([
            (object)["id" => 1, "title" => "Title A", "timestamp" => 100]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ChildDTO::class, $data, 0);

        $item = $items->current();
        $this->assertEquals(0, $item->getId());
        $this->assertEquals("Title A", $item->getTitle());
        $this->assertEquals(100, $item->getTimestamp());
    }

    public function testConstructableObject(): void {
        $data = new \ArrayIterator([
            (object)["currency" => "EUR"],
            (object)["currency" => "CAD"],
            (object)["currency" => null],
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(SimpleDTO::class, $data);

        $item = $items->current();
        $this->assertInstanceOf(Currency::class, $item->currency);
        $this->assertEquals("EUR", (string)$item->currency);
        $items->next();

        $item = $items->current();
        $this->assertInstanceOf(Currency::class, $item->currency);
        $this->assertEquals("CAD", (string)$item->currency);
        $items->next();

        $item = $items->current();
        $this->assertNull($item->currency);
        $items->next();
    }

    public function testNullableDeserializable(): void {

        $data = new \ArrayIterator([
            (object)["staticConstructible" => "TEST"]
        ]);

        $builder = new ObjectBuilder();
        $items = $builder->build(ComplexSerializedDTO::class, $data);

        $item = $items->current();
        $this->assertInstanceOf(StaticConstructible::class, $item->staticConstructible);
    }
}
