<?php
namespace IsThereAnyDeal\Database\Tests\Tables;

use IsThereAnyDeal\Database\Tables\AliasFactory;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\ProductTable;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\TableA;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{

    protected function setUp(): void {
        $class = new \ReflectionClass(AliasFactory::class);
        $class->setStaticPropertyValue("counter", 1);
    }

    public function testConstruction(): void {

        $a1 = new TableA();
        $this->assertEquals("tbl_a", $a1->__name__);
        $this->assertEquals("t1.`column1`", (string)$a1->a);
        $this->assertEquals("t1.`column1`", $a1->a->fqn);
        $this->assertEquals("column1", $a1->a->name);

        $this->assertEquals("t1.`b`", (string)$a1->b);
        $this->assertEquals("t1.`b`", $a1->b->fqn);
        $this->assertEquals("b", $a1->b->name);
    }

    public function testAliasing(): void {

        $a1 = new TableA();
        $this->assertEquals("`tbl_a` as `t1`", (string)$a1);

        $a2 = new TableA();
        $this->assertEquals("`tbl_a` as `t2`", (string)$a2);

        $b = new ProductTable();
        $this->assertEquals("`product` as `t3`", (string)$b);
    }
}
