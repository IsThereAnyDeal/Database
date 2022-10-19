<?php
namespace IsThereAnyDeal\Database\Sql;

class SubQuery
{
    public string $query;
    public array $params;

    /**
     * @param string $query
     * @param array $params
     */
    public function __construct(string $query="", array $params=[]) {
        $this->query = $query;
        $this->params = $params;
    }
}
