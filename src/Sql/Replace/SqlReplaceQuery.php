<?php
namespace IsThereAnyDeal\Database\Sql\Replace;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\SqlInsertQuery;
use IsThereAnyDeal\Database\Sql\Tables\Table;

class SqlReplaceQuery extends SqlInsertQuery {

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db, $table);
        $this->replace = true;
    }
}
