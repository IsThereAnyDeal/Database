<?php
namespace Database\Sql;

class ColumnList
{
    private array $columns;

    public function __construct(Column ...$columns) {
        $this->columns = $columns;
    }

    public function add(Column $column): void {
        $this->columns[] = $column;
    }

    public function toArray(): array {
        return $this->columns;
    }

    public function __toString(): string {
        return implode(", ", $this->columns);
    }
}
