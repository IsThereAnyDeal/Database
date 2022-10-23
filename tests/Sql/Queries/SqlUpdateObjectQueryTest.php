<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\ImplicitFullTableUpdateException;
use IsThereAnyDeal\Database\Sql\Tables\AliasFactory;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;
use IsThereAnyDeal\Database\Sql\Update\SqlUpdateObjectQuery;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[TableName("tbl")]
class SimpleTable extends Table {
    public readonly Column $a;
    public readonly Column $b;
    public readonly Column $c;
}

function getSimpleTableDTO(int $v1, int $v2, int $v3=0) {
    return new class($v1, $v2, $v3){
        private int $a;
        private int $b;
        private int $c;

        public function __construct($a, $b, $c=0) {
            $this->a = $a;
            $this->b = $b;
            $this->c = $c;
        }
    };
}


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
            ->update(getSimpleTableDTO(100, 200, 300));
    }

    public function testFullTableUpdateCheck(): void {
        $t = new SimpleTable();

        $this->expectException(ImplicitFullTableUpdateException::class);

        (new SqlUpdateObjectQuery($this->driverMock, $t))
            ->columns($t->a, $t->b, $t->c)
            ->update(getSimpleTableDTO(100, 200, 300));
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
            ->update(getSimpleTableDTO(100, 200, 300));
    }

}
