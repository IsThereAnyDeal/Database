<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Tables\Column;

interface IInsertable {
    public function getDbValue(Column $column);
}
