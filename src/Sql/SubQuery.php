<?php
namespace IsThereAnyDeal\Database\Sql;

class SubQuery
{
    /**
     * @param string $query
     * @param array<string, mixed> $params
     */
    public function __construct(
        public readonly string $query="",
        public readonly array $params=[]) {}
}
