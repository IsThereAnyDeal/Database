<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Tables;

use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

#[TableName("tbl")]
class SimpleTable extends Table
{
    public readonly Column $a;
    public readonly Column $b;
    public readonly Column $c;

    public static function getDTO(int $v1, int $v2, int $v3=0): object {
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
}

