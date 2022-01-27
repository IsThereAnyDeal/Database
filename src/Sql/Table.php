<?php
namespace Database\Sql;

abstract class Table
{
    private string $tableName;
    private string $tableAlias;

    public function __construct(string $name, array $columns, string $alias="") {
        $this->tableName = $name;
        $this->tableAlias = $alias;

        foreach($columns as $c) {
            $this->{$c} = $this->column($c);
        }
    }

    public function getName(): string {
        return $this->tableName;
    }

    protected function column(string $name): Column {
        return new Column(
            empty($this->tableAlias) ? $this->tableName : $this->tableAlias,
            $name
        );
    }

    final public function __toString(): string {
        return $this->tableName
            .(empty($this->tableAlias) ? "" : " as `{$this->tableAlias}`");
    }
}
