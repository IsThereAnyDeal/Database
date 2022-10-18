<?php
namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Attributes\TableColumn;
use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

#[TableName("tbl_a")]
class TableA extends Table
{
    #[TableColumn("column1")]
    public Column $a;

    public Column $b;
}
