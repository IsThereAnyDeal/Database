<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use Ds\Set;
use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\Exceptions\InvalidValueTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingDataException;
use PHPUnit\Framework\TestCase;
use stdClass;

enum EString: string {
    case ValueA = "A";
    case ValueB = "B";
    case ValueC = "C";
}

enum EInt: int {
    case Value1 = 1;
    case Value2 = 2;
    case Value3 = 3;
}

enum EUnit {
    case FirstValue;
    case SecondValue;

    public function serialize(): string {
        return match($this) {
            self::FirstValue => "first",
            self::SecondValue => "second"
        };
    }

    public function serializePair(): array {
        return match($this) {
            self::FirstValue => [1, "first"],
            self::SecondValue => [2, "second"]
        };
    }

    public static function staticSerialize(EUnit $value): string {
        return match($value) {
            self::FirstValue => "first",
            self::SecondValue => "second"
        };
    }

    public static function staticSerializePair(EUnit $value): array {
        return match($value) {
            self::FirstValue => [1, "first"],
            self::SecondValue => [2, "second"]
        };
    }
}


class ValueMapperTest extends TestCase
{
    public static function sampleArraySerializer(array $array): array {
        return array_values($array);
    }

    public function testSimpleProperties(): void {

        $obj = new class{
            public ?string $nullableProperty = null;
            public int $intProperty = 24;
            public bool $boolProperty = true;
        };

        $columns = new Set(["nullableProperty", "intProperty", "boolProperty"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals([null, 24, true], $values);
    }

    public function testEnumProperties(): void {
        $obj = new class{
            public EString $stringEnum = EString::ValueA;
            public EInt $intEnum = EInt::Value3;
        };

        $columns = new Set(["stringEnum", "intEnum"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals(["A", 3], $values);
    }

    public function testSerializable(): void {

        $obj = new class {
            #[Column(["c1", "c2"], serializer: [ValueMapperTest::class, "sampleArraySerializer"])]
            public array $serializable = [1, "A"];

            #[Column(serializer: "@serialize")]
            public EUnit $enum1 = EUnit::SecondValue;

            #[Column(["c4", "c5"], "@serializePair")]
            public EUnit $enum2 = EUnit::FirstValue;

            #[Column("c6", [EUnit::class, "staticSerialize"])]
            public EUnit $enum3 = EUnit::SecondValue;

            #[Column(["c7", "c8"], serializer: [EUnit::class, "staticSerializePair"])]
            public EUnit $enum4 = EUnit::FirstValue;
        };

        $columns = new Set(["c1", "c2", "enum1", "c4", "c5", "c6", "c7", "c8"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals([1, "A", "second", "1", "first", "second", "1", "first"], $values);
    }

    public function testValuesOrder(): void {
        $obj = new class {
            public int $a = 1;
            public int $b = 2;
            public int $c = 3;
            public int $d = 4;
            public int $e = 5;
        };

        $columns = new Set(["d", "e", "c", "a", "b"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals([4, 5, 3, 1, 2], $values);
    }

    public function testResultSubset(): void {
        $obj = new class {
            #[Column(["c1", "c2", "c3"], serializer: [ValueMapperTest::class, "sampleArraySerializer"])]
            public array $set1 = [1, 2, 3];

            public int $c4 = 4;
            public int $c5 = 5;

            #[Column(["c6", "c7"], serializer: [ValueMapperTest::class, "sampleArraySerializer"])]
            public array $set2 = [6, 7];
        };

        $columns = new Set(["c1", "c4", "c3",]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals([1, 4, 3], $values);
    }

    public function testInvalidType(): void {
        $objValue = new stdClass();
        $objValue->value = "some value";

        $obj = new class($objValue){
            public object $objProperty;

            public function __construct($value) {
                $this->objProperty = $value;
            }
        };

        $columns = new Set(["objProperty"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $this->expectException(InvalidValueTypeException::class);
        call_user_func($mapper, $obj);
    }

    public function testMissingData(): void {

        $obj = new class{
            public int $prop1 = 1;
            public int $prop2 = 2;
            public int $prop3 = 3;
        };

        $columns = new Set(["prop1", "prop4"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $this->expectException(MissingDataException::class);
        $this->expectExceptionMessage("Missing data for column 'prop4'");
        call_user_func($mapper, $obj);
    }

    public function testOverlapData(): void {

        $obj = new class{
            public int $prop1 = 1;
            #[Column("prop1")]
            public int $prop2 = 2;
            #[Column("prop1")]
            public int $prop3 = 3;
        };

        $columns = new Set(["prop1"]);
        $mapper = ValueMapper::getObjectValueMapper($columns, $obj);

        $values = call_user_func($mapper, $obj);
        $this->assertEquals([3], $values);
    }
}
