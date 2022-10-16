<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Tables\Column;

class AInsertableObject implements IInsertable
{
    public function getDbValue(Column $column) {
        return $this->{$column->name};
    }
}
