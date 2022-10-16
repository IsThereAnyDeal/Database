<?php
namespace IsThereAnyDeal\Database\Sql\Tables;

class ColumnList
{
    /** @var array<Column> */
    private array $columns;

    public function __construct(Column ...$columns) {
        $this->columns = $columns;
    }

    public function add(Column $column): void {
        $this->columns[] = $column;
    }

    /**
     * @return array<Column>
     */
    public function toArray(): array {
        return $this->columns;
    }

    public function __toString(): string {
        return implode(", ", $this->columns);
    }
}
