<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\SqlResult;
use IsThereAnyDeal\Database\Sql\SqlSelectQuery;
use IsThereAnyDeal\Database\TestObjects\ProductDTO;
use IsThereAnyDeal\Database\TestObjects\ProductTable;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlSelectQueryTest extends TestCase
{
    private MockObject $pdoMock;
    private MockObject $driverMock;

    protected function setUp(): void {
        $this->pdoMock = $this->createMock(PDO::class);

        $this->driverMock = $this->createMock(DbDriver::class);
        $this->driverMock->expects($this->once())
            ->method("getDriver")
            ->willReturn($this->pdoMock);
    }

    public function testSimpleSelect(): void {

        $statementMock = $this->createMock(\PDOStatement::class);
        $statementMock->expects($this->once())
            ->method("setFetchMode")
            ->with(PDO::FETCH_OBJ);
        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method("prepare")
            ->with("SELECT t1.`name`, t1.`price`, t1.`currency` FROM product as `t1`")
            ->willReturn($statementMock);

        $t = new ProductTable();
        $select = (new SqlSelectQuery($this->driverMock,
            "SELECT $t->name, $t->price, $t->currency FROM $t"))
            ->fetch();

        $this->assertInstanceOf(SqlResult::class, $select);
    }

    public function testFetchWithParams(): void {
        $statementMock = $this->createMock(\PDOStatement::class);
        $statementMock->expects($this->once())
            ->method("setFetchMode")
            ->with(PDO::FETCH_CLASS);
        $statementMock->expects($this->once())
            ->method("execute")
            ->willReturn(true);
        $statementMock->expects($this->exactly(1))
            ->method("bindValue")
            ->withConsecutive([1, "Sample", PDO::PARAM_STR]);

        $this->pdoMock->expects($this->once())
            ->method("prepare")
            ->willReturn($statementMock);

        $t = new ProductTable();
        $select = (new SqlSelectQuery($this->driverMock,
            "SELECT $t->name, $t->price, $t->currency
                FROM $t
                WHERE $t->name=:name"
            ))->params([
                ":name" => "Sample"
            ])
            ->fetch(ProductDTO::class);

        $this->assertInstanceOf(SqlResult::class, $select);
    }
}