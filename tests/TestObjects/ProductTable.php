<?php
namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\Attributes\TableName;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

#[TableName("product")]
class ProductTable extends Table
{
    public Column $name;
    public Column $price;
    public Column $currency;
}
