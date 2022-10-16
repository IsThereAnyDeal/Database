<?php

namespace IsThereAnyDeal\Database\TestObjects;

use IsThereAnyDeal\Database\Sql\AInsertableObject;

class TableA_DTO extends AInsertableObject
{
    protected int $a;
    protected string $b;

    public function getA(): int {
        return $this->a;
    }

    public function setA(int $a): self {
        $this->a = $a;
        return $this;
    }

    public function getB(): string {
        return $this->b;
    }

    public function setB(string $b): self {
        $this->b = $b;
        return $this;
    }
}
