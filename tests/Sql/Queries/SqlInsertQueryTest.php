<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\SqlInsertQuery;
use IsThereAnyDeal\Database\Sql\Tables\AliasFactory;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;
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


class SqlInsertQueryTest extends TestCase
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

    public function testSimpleInsert(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(3))
            ->method("bindValue")
            ->withConsecutive(
                [1, 1, PDO::PARAM_INT],
                [2, 2, PDO::PARAM_INT],
                [3, 3, PDO::PARAM_INT],
            );

        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $statementMock->expects($this->once())
            ->method("rowCount")
            ->willReturn(1);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("INSERT INTO `tbl` (`a`,`b`,`c`)\nVALUES (?,?,?)")
            ->willReturn($statementMock);

        $insert = (new SqlInsertQuery($this->driverMock, $t))
            ->columns($t->a, $t->b, $t->c)
            ->persist(new class{
                private int $a = 1;
                private int $b = 2;
                private int $c = 3;
            });

        $this->assertEquals(1, $insert->getInsertedRowCount());
    }

    public function testIgnoreInsert(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->once())
            ->method("bindValue")
            ->withAnyParameters();

        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $statementMock->expects($this->once())
            ->method("rowCount")
            ->willReturn(1);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("INSERT IGNORE INTO `tbl` (`a`)\nVALUES (?)")
            ->willReturn($statementMock);

        $insert = (new SqlInsertQuery($this->driverMock, $t))
            ->ignore()
            ->columns($t->a)
            ->persist(new class{
                private int $a = 1;
                private int $b = 2;
                private int $c = 3;
            });

        $this->assertEquals(1, $insert->getInsertedRowCount());
    }

    public function testMultiValueInsert(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(4))
            ->method("bindValue")
            ->withConsecutive(
                [1, 101, PDO::PARAM_INT],
                [2, 102, PDO::PARAM_INT],
                [3, 201, PDO::PARAM_INT],
                [4, 202, PDO::PARAM_INT],
            );

        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $statementMock->expects($this->once())
            ->method("rowCount")
            ->willReturn(2);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("INSERT IGNORE INTO `tbl` (`a`,`b`)\nVALUES (?,?),\n(?,?)")
            ->willReturn($statementMock);

        $insert = (new SqlInsertQuery($this->driverMock, $t))
            ->ignore()
            ->columns($t->a, $t->b)
            ->stack(getSimpleTableDTO(101, 102))
            ->stack(getSimpleTableDTO(201, 202))
            ->persist();

        $this->assertEquals(2, $insert->getInsertedRowCount());
    }

    public function testStackSizeInsert(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(6))
            ->method("bindValue")
            ->withConsecutive(
                [1, 101, PDO::PARAM_INT], [2, 102, PDO::PARAM_INT],
                [1, 201, PDO::PARAM_INT], [2, 202, PDO::PARAM_INT],
                [1, 301, PDO::PARAM_INT], [2, 302, PDO::PARAM_INT],
            );

        $statementMock->expects($this->exactly(3))
            ->method("execute")
            ->willReturn(true);

        $statementMock->expects($this->exactly(3))
            ->method("rowCount")
            ->willReturnOnConsecutiveCalls(1, 1, 1);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("INSERT INTO `tbl` (`a`,`b`)\nVALUES (?,?)")
            ->willReturn($statementMock);

        $insert = (new SqlInsertQuery($this->driverMock, $t))
            ->stackSize(1)
            ->columns($t->a, $t->b)
            ->stack(getSimpleTableDTO(101, 102))
            ->stack(getSimpleTableDTO(201, 202))
            ->stack(getSimpleTableDTO(301, 302));

        $insert->persist(); // not saving anything

        $this->assertEquals(3, $insert->getInsertedRowCount());
    }

    public function testEmptyInsert(): void {
        $t = new SimpleTable();

        $this->pdoMock
            ->expects($this->exactly(0))
            ->method("prepare");

        $insert = (new SqlInsertQuery($this->driverMock, $t))
            ->columns($t->a, $t->b)
            ->persist();

        $this->assertEquals(0, $insert->getInsertedRowCount());
    }

    public function testOnDuplicateUpdate(): void {
        $t = new SimpleTable();

        $statementMock = $this->createMock(PDOStatement::class);

        $statementMock->expects($this->exactly(2))
            ->method("bindValue")
            ->withConsecutive(
                [1, 101, PDO::PARAM_INT],
                [2, 102, PDO::PARAM_INT]
            );

        $statementMock->expects($this->exactly(1))
            ->method("execute")
            ->willReturn(true);

        $this->pdoMock
            ->expects($this->once())
            ->method("prepare")
            ->with("INSERT INTO `tbl` (`a`,`b`)\n"
                ."VALUES (?,?)\n"
                ."ON DUPLICATE KEY UPDATE `a`=VALUES(`a`),`b`=VALUES(`b`),`c`=a + b")
            ->willReturn($statementMock);

        (new SqlInsertQuery($this->driverMock, $t))
            ->columns($t->a, $t->b)
            ->onDuplicateKeyUpdate($t->a, $t->b)
            ->onDuplicateKeyExpression($t->c, "{$t->a->name} + {$t->b->name}")
            ->persist(getSimpleTableDTO(101, 102));
    }
}
