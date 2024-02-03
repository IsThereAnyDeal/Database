<?php
namespace IsThereAnyDeal\Database\Tables;

final readonly class Column
{
    private Table $table;
    public string $name;
    public string $aliased;

    public function __construct(Table $table, string $name) {
        $this->table = $table;
        $this->name = $name;
        $this->aliased = (empty($table->__alias__)
            ? $this->fqn()
            : "{$table->__alias__}.`{$name}`");
    }

    public function fqn(): string {
        return "{$this->table->__name__}.`{$this->name}`";
    }

    public function as(string $alias): string {
        return "{$this->fqn()} as `{$alias}`";
    }

    public function __toString(): string {
        return $this->aliased;
    }
}
