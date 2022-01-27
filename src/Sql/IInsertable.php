<?php
namespace IsThereAnyDeal\Database\Sql;

interface IInsertable {
    public function getDbValue(Column $column);
}
