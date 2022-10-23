<?php
namespace IsThereAnyDeal\Database\Sql\Create;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Tables\Table;

class SqlReplaceQuery extends SqlInsertQuery {

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db, $table);
        $this->replace = true;
    }
}
