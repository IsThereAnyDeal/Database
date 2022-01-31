<?php
namespace IsThereAnyDeal\Database\Sql;

use ReflectionClass;
use ReflectionProperty;

abstract class Table
{
    private string $tableName;
    private string $tableAlias;

    public function __construct(string $name, array $columns, string $alias="") {
        $this->tableName = $name;
        $this->tableAlias = $alias;

        if (empty($columns)) {
            $reflection = new ReflectionClass($this);
            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $type = $property->getType();
                $typeName = $type->getName();

                $propertyName = $property->getName();

                if ($typeName === Column::class) {
                    $this->{$propertyName} = $this->column($propertyName);
                } elseif (class_exists($typeName)) {
                    $reflectionProperty = new ReflectionClass($type->getName());
                    if ($reflectionProperty->isSubclassOf(Column::class)) {
                        $this->{$propertyName} = $this->column($propertyName);
                    }
                }
            }
        } else {
            foreach($columns as $c) {
                $this->{$c} = $this->column($c);
            }
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
