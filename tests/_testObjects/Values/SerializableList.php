<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Values;

class SerializableList
{
    private array $data = [];

    public function add($item): self {
        $this->data[] = $item;
        return $this;
    }

    public function getValues(): array {
        return array_values($this->data);
    }

    public function fromString(string $data): self {
        if (!empty($data)) {
            $this->data = explode(",", $data);
        }
        return $this;
    }

    public function toString(): ?string {
        return empty($this->data)
            ? null
            : implode(",", $this->data);
    }
}
