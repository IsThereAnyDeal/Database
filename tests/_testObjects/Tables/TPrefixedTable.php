<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Tables;

use IsThereAnyDeal\Database\Attributes\TableColumn;
use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

#[TableName("tbl_prefixed_table")]
class TPrefixedTable extends Table
{
    #[TableColumn("id")]
    public Column $id;
}
