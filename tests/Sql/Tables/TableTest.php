<?php
namespace IsThereAnyDeal\Database\Sql\Tables;

use IsThereAnyDeal\Database\TestObjects\ProductTable;
use IsThereAnyDeal\Database\TestObjects\TableA;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{

    protected function setUp(): void {
        $class = new \ReflectionClass(AliasFactory::class);
        $class->setStaticPropertyValue("counter", 1);
    }

    public function testConstruction(): void {

        $a1 = new TableA();
        $this->assertEquals("tbl_a", $a1->getName());
        $this->assertEquals("t1.`column1`", (string)$a1->a);
        $this->assertEquals("t1.`column1`", $a1->a->fqn);
        $this->assertEquals("column1", $a1->a->name);

        $this->assertEquals("t1.`b`", (string)$a1->b);
        $this->assertEquals("t1.`b`", $a1->b->fqn);
        $this->assertEquals("b", $a1->b->name);
    }

    public function testAliasing(): void {

        $a1 = new TableA();
        $this->assertEquals("tbl_a as `t1`", (string)$a1);

        $a2 = new TableA();
        $this->assertEquals("tbl_a as `t2`", (string)$a2);

        $b = new ProductTable();
        $this->assertEquals("product as `t3`", (string)$b);
    }
}
