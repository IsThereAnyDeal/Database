<?php
namespace IsThereAnyDeal\Database\Sql\Queries;

use IsThereAnyDeal\Database\Exceptions\ResultsClosedException;
use IsThereAnyDeal\Database\Sql\Select\SqlResult;
use PHPUnit\Framework\TestCase;

class SqlResultTest extends TestCase
{
    private function getResultWithData(array $data): SqlResult {
        $traversable = new \NoRewindIterator(new \ArrayIterator($data));
        return new SqlResult($traversable, count($data));
    }

    public function testGetOne(): void {
        $result = $this->getResultWithData([
            (object)["id" => 999],
            (object)["id" => 1000],
            (object)["id" => 1002],
        ]);
        $this->assertEquals(999, $result->getOne()->id);

        $this->expectException(ResultsClosedException::class);
        $result->getOne();
    }

    public function testGetOneMapped(): void {
        $result = $this->getResultWithData([
            (object)["id" => 999],
            (object)["id" => 1000],
            (object)["id" => 1002],
        ]);

        $mapper = function(object $o) {
            $o->id -= 499; return $o;
        };
        $this->assertEquals(500, $result->getOne($mapper)->id);
    }

    public function testToArray(): void {
        $result = $this->getResultWithData([1, 5, 18]);
        $this->assertEquals([1, 5, 18], $result->toArray());

        $this->expectException(ResultsClosedException::class);
        $result->toArray();
    }

    public function testToArrayMapped(): void {
        $result = $this->getResultWithData([
            (object)["id" => 1],
            (object)["id" => 5],
            (object)["id" => 18],
        ]);

        $this->assertEquals([1, 5, 18], $result->toArray(fn($o) => $o->id));

        $this->expectException(ResultsClosedException::class);
        $result->toArray();
    }

    public function testToMap(): void {
        $result = $this->getResultWithData([
            (object)["id" => 1, "key" => "a"],
            (object)["id" => 5, "key" => "b"],
            (object)["id" => 18, "key" => "c"],
        ]);

        $mapper = fn($o) => [$o->key, $o->id];
        $this->assertEquals([
            "a" => 1,
            "b" => 5,
            "c" => 18
        ], $result->toMap($mapper));

        $this->expectException(ResultsClosedException::class);
        $result->toMap($mapper);
    }

    public function testToGroups(): void {
        $result = $this->getResultWithData([
            (object)["id" => 1, "key" => "a"],
            (object)["id" => 5, "key" => "a"],
            (object)["id" => 18, "key" => "b"],
        ]);

        $mapper = fn($o) => [$o->key, $o->id];
        $this->assertEquals([
            "a" => [1, 5],
            "b" => [18]
        ], $result->toGroups($mapper));

        $this->expectException(ResultsClosedException::class);
        $result->toGroups($mapper);
    }
}
