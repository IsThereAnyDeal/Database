<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;

class SqlReplaceQuery extends SqlInsertQuery {

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db, $table);
        $this->replace = true;
    }
}
