<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Tables;

use IsThereAnyDeal\Database\Attributes\TableColumn;
use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

#[TableName("tbl_a")]
class TableA extends Table
{
    #[TableColumn("column1")]
    public Column $a;

    public Column $b;
}
