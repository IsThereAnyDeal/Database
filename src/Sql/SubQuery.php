<?php
namespace IsThereAnyDeal\Database\Sql;

use BackedEnum;

class SubQuery
{
    /**
     * @param string $query
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     */
    public function __construct(
        public readonly string $query="",
        public readonly array $params=[]) {}
}
