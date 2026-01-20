<?php
namespace IsThereAnyDeal\Database\Tables;

class Context
{
    private int $counter = 1;

    public function getAlias(): string {
        return "t".($this->counter++);
    }
}
