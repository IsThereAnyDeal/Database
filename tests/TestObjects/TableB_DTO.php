<?php

namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\AInsertableObject;

class TableB_DTO extends AInsertableObject
{
    private string $column_1;
    private int $column_2;
    private StorableObject $column_3;

    public function getColumn1(): string {
        return $this->column_1;
    }

    public function setColumn1(string $column_1): self {
        $this->column_1 = $column_1;
        return $this;
    }

    public function getColumn2(): int {
        return $this->column_2;
    }

    public function setColumn2(int $column_2): self {
        $this->column_2 = $column_2;
        return $this;
    }

    public function getColumn3(): StorableObject {
        return $this->column_3;
    }

    public function setColumn3(StorableObject $column_3): self {
        $this->column_3 = $column_3;
        return $this;
    }
}
