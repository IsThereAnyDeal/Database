<?php
namespace IsThereAnyDeal\Database\Tables;

class AliasFactory
{
    private static int $counter = 1;

    public static function getAlias(): string {
        return "t".(self::$counter++);
    }
}
