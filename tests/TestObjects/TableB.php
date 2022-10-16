<?php
namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\Attributes\TableName;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

#[TableName("tbl_b")]
class TableB extends Table
{
    public Column $column_1;
    public Column $column_2;
    public Column $column_3;
}
