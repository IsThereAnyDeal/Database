<?php
namespace Database\Sql;

final class Column
{
    private string $name;
    private string $column;

    public function __construct(string $table, string $name) {
        $this->name = $name;
        $this->column = (empty($table) ? "`$name`" : "{$table}.`{$name}`");
    }

    public function getName(): string {
        return $this->name;
    }

    public function getQuerySafeName(): string {
        return "`{$this->name}`";
    }

    public function as(string $alias): string {
        return "{$this->column} as `{$alias}`";
    }

    public function __toString(): string {
        return $this->column;
    }
}
