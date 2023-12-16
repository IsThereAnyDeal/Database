<?php
namespace IsThereAnyDeal\Database\Sql;

use BackedEnum;

class SubQuery
{
    /**
     * @param string $query
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @param list<string> $hints
     */
    public function __construct(
        public string $query="",
        public array $params=[],
        public readonly array $hints=[]
    ) {}
}
