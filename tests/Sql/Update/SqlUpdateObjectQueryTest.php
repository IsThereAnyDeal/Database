<?php
namespace IsThereAnyDeal\Database\Tests\Sql\Update;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\ImplicitFullTableUpdateException;
use IsThereAnyDeal\Database\Sql\Update\SqlUpdateObjectQuery;
use IsThereAnyDeal\Database\Tables\AliasFactory;
use IsThereAnyDeal\Database\Tests\_testObjects\Tables\SimpleTable;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlUpdateObjectQueryTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $driverMock;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);

        $this->driverMock = $this->createMock(DbDriver::class);
        $this->driverMock
            ->method("getDriver")
            ->willReturn($this->pdoMock);

        $class = new \ReflectionClass(AliasFactory::class);
        $class->setStaticPropertyValue("counter", 1);
    }

    public function testSimpleUpdate(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(3))
            ->method("bindValue")
            ->withConsecutive(
                [1, 100, PDO::PARAM_INT],
                [2, 200, PDO::PARAM_INT],
                [3, 300, PDO::PARAM_INT],
            );

        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("UPDATE `tbl` as `t1`\nSET `a`=?, `b`=?, `c`=?")
            ->willReturn($statementMock);

        (new SqlUpdateObjectQuery($this->driverMock, $t))
            ->fullTableUpdate()
            ->columns($t->a, $t->b, $t->c)
            ->update(SimpleTable::getDTO(100, 200, 300));
    }

    public function testFullTableUpdateCheck(): void {
        $t = new SimpleTable();

        $this->expectException(ImplicitFullTableUpdateException::class);

        (new SqlUpdateObjectQuery($this->driverMock, $t))
            ->columns($t->a, $t->b, $t->c)
            ->update(SimpleTable::getDTO(100, 200, 300));
    }

    public function testWhere(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(4))
            ->method("bindValue")
            ->withConsecutive(
                [1, 100, PDO::PARAM_INT],
                [2, 300, PDO::PARAM_INT],
                [3, 200, PDO::PARAM_INT],
                [4, -1, PDO::PARAM_INT],
            );

        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("UPDATE `tbl` as `t1`\n"
                ."SET `a`=?\n"
                ."WHERE `c`=? AND `b`=? AND t1.`b` > ?"
            )
            ->willReturn($statementMock);

        (new SqlUpdateObjectQuery($this->driverMock, $t))
            ->columns($t->a)
            ->where($t->c, $t->b)
            ->whereSql("$t->b > :bcond", [
                ":bcond" => -1
            ])
            ->update(SimpleTable::getDTO(100, 200, 300));
    }

}
