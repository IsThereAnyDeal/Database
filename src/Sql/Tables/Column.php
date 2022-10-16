<?php
namespace IsThereAnyDeal\Database\Sql\Tables;

final class Column
{
    public readonly string $name;
    public readonly string $fqn;

    public function __construct(string $table, string $name) {
        $this->name = $name;
        $this->fqn = (empty($table) ? "`$name`" : "{$table}.`{$name}`");
    }

    public function as(string $alias): string {
        return "{$this->fqn} as `{$alias}`";
    }

    public function __toString(): string {
        return $this->fqn;
    }
}
