<?php
namespace IsThereAnyDeal\Database\TestObjects;

class StorableObject
{
    private string $value;

    public function getValue(): string {
        return $this->value;
    }

    public function setValue(string $value): self {
        $this->value = $value;
        return $this;
    }
}
