<?php
namespace Tables;

use IsThereAnyDeal\Database\Tables\Registry;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\SimpleTable;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\TableA;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\TableA2;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\TPrefixedTable;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    /**
     * Create fresh registry for each test
     */
    protected function setUp(): void {
        $rc = new \ReflectionClass(Registry::class);

        $constructor = $rc->getConstructor();
        if (is_null($constructor)) {
            throw new \ErrorException();
        }

        $instance = $rc->newInstanceWithoutConstructor();
        $constructor->invoke($instance);

        $rc->setStaticPropertyValue("instance", $instance);
    }

    public function testRegistry(): void {
        $r1 = Registry::context();
        $c1_ta1 = $r1->get(TableA::class);
        $c1_ta2 = $r1->get(TableA::class);
        $c1_st = $r1->get(SimpleTable::class);
        $c1_p = $r1->get(TPrefixedTable::class);
        $c1_ta3 = $r1->get(TableA::class);

        $r2 = Registry::context();
        $c2_p1 = $r2->get(TPrefixedTable::class);
        $c2_p2 = $r2->get(TPrefixedTable::class);
        $c2_ta1 = $r2->get(TableA::class);
        $c2_ta2 = $r2->get(TableA::class);
        $c2_ta3 = $r2->get(TableA::class);

        $this->assertEquals("ta_.`column1`", (string)$c1_ta1->a);
        $this->assertEquals("ta2_.`column1`", (string)$c1_ta2->a);
        $this->assertEquals("st_.`c`", (string)$c1_st->c);
        $this->assertEquals("pt_.`id`", (string)$c1_p->id);
        $this->assertEquals("ta3_.`column1`", (string)$c1_ta3->a);

        $this->assertEquals("ta_.`column1`", (string)$c2_ta1->a);
        $this->assertEquals("ta2_.`column1`", (string)$c2_ta2->a);
        $this->assertEquals("ta3_.`column1`", (string)$c2_ta3->a);
        $this->assertEquals("pt_.`id`", (string)$c2_p1->id);
        $this->assertEquals("pt2_.`id`", (string)$c2_p2->id);

        $this->assertSame($c1_ta1, $c2_ta1);
        $this->assertSame($c1_ta2, $c2_ta2);
        $this->assertSame($c1_ta3, $c2_ta3);

        $this->assertNotSame($c1_ta1, $c1_ta2);
        $this->assertNotSame($c1_ta1, $c1_ta3);
        $this->assertNotSame($c1_ta2, $c1_ta3);
    }

    /**
     * Table's whose class names are in format TTableName will drop the first T
     */
    public function testPrefixedAlias(): void {
        $r = Registry::context();

        $t1 = $r->get(TPrefixedTable::class);
        $t2 = $r->get(TPrefixedTable::class);
        $this->assertEquals("`tbl_prefixed_table` as `pt_`", (string)$t1);
        $this->assertEquals("`tbl_prefixed_table` as `pt2_`", (string)$t2);
    }

    /**
     * Tables that generate same alias should share context
     */
    public function testConflictAliases(): void {
        $r1 = Registry::context();
        $t1 = $r1->get(TableA::class);
        $t2 = $r1->get(TableA2::class);
        $t3 = $r1->get(TableA::class);

        $this->assertEquals("ta_.`b`", (string)$t1->b);
        $this->assertEquals("ta2_.`b`", (string)$t2->b);
        $this->assertEquals("ta3_.`b`", (string)$t3->b);

        $r2 = Registry::context();
        $r2_t2 = $r2->get(TableA2::class);

        $this->assertEquals("ta2_.`b`", (string)$r2_t2->b);
    }
}
