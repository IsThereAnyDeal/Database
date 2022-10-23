<?php
namespace IsThereAnyDeal\Database\Tests\Sql;

use IsThereAnyDeal\Database\Exceptions\InvalidValueCountException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\EInt;
use IsThereAnyDeal\Database\Tests\_testObjects\Enum\EString;
use PHPUnit\Framework\TestCase;

class ParamParserTest extends TestCase
{
    public function testNoParam(): void {
        $params = new ParamParser("WHERE 1");

        $this->assertEquals("WHERE 1", $params->getQuery());
        $this->assertEquals([], $params->getValues());
    }

    public function testSimpleParam(): void {
        $params = new ParamParser("WHERE column=:param", [
            ":param" => "value"
        ]);

        $this->assertEquals("WHERE column=?", $params->getQuery());
        $this->assertEquals(["value"], $params->getValues());
    }

    public function testMultiuseParam(): void {
        $params = new ParamParser("WHERE columnA=:param AND columnB=:param", [
            ":param" => "value"
        ]);

        $this->assertEquals("WHERE columnA=? AND columnB=?", $params->getQuery());
        $this->assertEquals(["value", "value"], $params->getValues());
    }

    public function testArrayParam(): void {
        $params = new ParamParser("WHERE column IN :param", [
            ":param" => [1, 2, 3, 4, 5]
        ]);

        $this->assertEquals("WHERE column IN (?,?,?,?,?)", $params->getQuery());
        $this->assertEquals([1, 2, 3, 4, 5], $params->getValues());
    }

    public function testTuplesParam(): void {
        $params = new ParamParser("WHERE (column_a, column_b) IN :param(2)", [
            ":param" => [
                "a", 1,
                "b", 2,
                "c", 3,
                "d", 4
            ],
        ]);

        $this->assertEquals("WHERE (column_a, column_b) IN ((?,?),(?,?),(?,?),(?,?))", $params->getQuery());
        $this->assertEquals(["a", 1, "b", 2, "c", 3, "d", 4], $params->getValues());

        // small
        $params = new ParamParser("WHERE (column_a, column_b) IN :param(2)", [
            ":param" => ["a", 1],
        ]);

        $this->assertEquals("WHERE (column_a, column_b) IN ((?,?))", $params->getQuery());
        $this->assertEquals(["a", 1], $params->getValues());
    }

    public function testInvalidValueCount(): void {
        $this->expectException(InvalidValueCountException::class);

        new ParamParser("WHERE (column_a, column_b) IN :param(2)", [
            ":param" => [
                "a", 1,
                "b"
            ],
        ]);
    }

    public function testMissingParam(): void {
        $this->expectException(MissingParameterException::class);

        $params = new ParamParser("WHERE column=:missingParam", [
            ":param" => "value"
        ]);
    }

    public function testExtraParam(): void {
        $params = new ParamParser("WHERE column=:param", [
            ":param" => "value",
            ":extra" => "notused"
        ]);

        $this->assertEquals("WHERE column=?", $params->getQuery());
        $this->assertEquals(["value"], $params->getValues());
    }

    public function testOutOfOrderParams(): void {
        $params = new ParamParser("WHERE columnA=:p1 AND columnB=:p2 AND columnC=:p3", [
            ":p3" => "v3",
            ":p1" => "v1",
            ":p2" => "v2",
        ]);

        $this->assertEquals("WHERE columnA=? AND columnB=? AND columnC=?", $params->getQuery());
        $this->assertEquals(["v1", "v2", "v3"], $params->getValues());
    }

    public function testEnumParam(): void {
        $params = new ParamParser("WHERE columnA=:intEnum AND columnB=:strEnum", [
            ":intEnum" => EInt::Value2,
            ":strEnum" => EString::ValueC
        ]);

        $this->assertEquals("WHERE columnA=? AND columnB=?", $params->getQuery());
        $this->assertEquals([2, "C"], $params->getValues());

        $params = new ParamParser("WHERE columnA IN :intEnum AND columnB IN :strEnum", [
            ":intEnum" => [EInt::Value2, EInt::Value1],
            ":strEnum" => [EString::ValueC, EString::ValueA]
        ]);

        $this->assertEquals("WHERE columnA IN (?,?) AND columnB IN (?,?)", $params->getQuery());
        $this->assertEquals([2, 1, "C", "A"], $params->getValues());
    }

    public function testPairParam(): void {
        $params = new ParamParser("WHERE (columnA, columnB, columnC) IN :pairs(3)", [
            ":pairs" => [
                EInt::Value1, EString::ValueC, 500,
                EInt::Value3, EString::ValueB, 200,
                EInt::Value2, EString::ValueA, 100,
            ]
        ]);

        $this->assertEquals("WHERE (columnA, columnB, columnC) IN ((?,?,?),(?,?,?),(?,?,?))", $params->getQuery());
        $this->assertEquals([
            1, "C", 500,
            3, "B", 200,
            2, "A", 100
        ], $params->getValues());
    }
}
