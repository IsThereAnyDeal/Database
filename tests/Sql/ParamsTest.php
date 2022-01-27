<?php
namespace IsThereAnyDeal\Database\Sql;

use PHPUnit\Framework\TestCase;
use IsThereAnyDeal\Database\Sql\Exceptions\MissingParameterException;

class ParamsTest extends TestCase
{
    public function testNoParam(): void {
        $params = new Params("WHERE 1");

        $this->assertEquals("WHERE 1", $params->getQuery());
        $this->assertEquals([], $params->getParams());
        $this->assertEquals([], $params->getCounts());
    }

    public function testSimpleParam(): void {
        $params = new Params("WHERE column=:param", [
            ":param" => "value"
        ]);

        $this->assertEquals("WHERE column=?", $params->getQuery());
        $this->assertEquals(["value"], $params->getParams());
        $this->assertEquals([1], $params->getCounts());
    }

    public function testMultiuseParam(): void {
        $params = new Params("WHERE columnA=:param AND columnB=:param", [
            ":param" => "value"
        ]);

        $this->assertEquals("WHERE columnA=? AND columnB=?", $params->getQuery());
        $this->assertEquals(["value", "value"], $params->getParams());
        $this->assertEquals([1, 1], $params->getCounts());
    }

    public function testArrayParam(): void {
        $params = new Params("WHERE column IN :param", [
            ":param" => [1, 2, 3, 4, 5]
        ]);

        $this->assertEquals("WHERE column IN (?,?,?,?,?)", $params->getQuery());
        $this->assertEquals([1, 2, 3, 4, 5], $params->getParams());
        $this->assertEquals([5], $params->getCounts());
    }

    public function testMissingParam(): void {
        $this->expectException(MissingParameterException::class);

        $params = new Params("WHERE column=:missingParam", [
            ":param" => "value"
        ]);
    }

    public function testExtraParam(): void {
        $params = new Params("WHERE column=:param", [
            ":param" => "value",
            ":extra" => "notused"
        ]);

        $this->assertEquals("WHERE column=?", $params->getQuery());
        $this->assertEquals(["value"], $params->getParams());
        $this->assertEquals([1], $params->getCounts());
    }

    public function testOutOfOrderParams(): void {
        $params = new Params("WHERE columnA=:p1 AND columnB=:p2 AND columnC=:p3", [
            ":p3" => "v3",
            ":p1" => "v1",
            ":p2" => "v2",
        ]);

        $this->assertEquals("WHERE columnA=? AND columnB=? AND columnC=?", $params->getQuery());
        $this->assertEquals(["v1", "v2", "v3"], $params->getParams());
        $this->assertEquals([1, 1, 1], $params->getCounts());
    }


}
