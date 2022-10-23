<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Tables;

use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

#[TableName("product")]
class ProductTable extends Table
{
    public Column $name;
    public Column $price;
    public Column $currency;
}
