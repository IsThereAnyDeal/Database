<?php
namespace Tables;

use IsThereAnyDeal\Database\Tables\Registry;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\SimpleTable;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\TableA;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    public function testRegistry(): void {

        $r1 = Registry::context();
        $c1t1 = $r1->get(TableA::class);
        $c1t2 = $r1->get(TableA::class);
        $c1t3 = $r1->get(SimpleTable::class);

        $r2 = Registry::context();
        $c2t1 = $r2->get(TableA::class);

        $this->assertEquals("t1.`column1`", (string)$c1t1->a);
        $this->assertEquals("t2.`column1`", (string)$c1t2->a);
        $this->assertEquals("t3.`c`", (string)$c1t3->c);

        $this->assertEquals("t1.`column1`", (string)$c2t1->a);
        $this->assertSame($c1t1, $c2t1);
    }
}
