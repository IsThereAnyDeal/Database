<?php
namespace IsThereAnyDeal\Database\Sql;

class AInsertableObject implements IInsertable
{
    public function getDbValue(Column $column) {
        return $this->{$column->getName()};
    }
}
