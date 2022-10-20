<?php
namespace IsThereAnyDeal\Database\Sql\Tables;

use IsThereAnyDeal\Database\Attributes\TableColumn;
use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Exceptions\InvalidSetupException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

abstract class Table
{
    private readonly string $tbl_name;
    private readonly string $tbl_alias;

    public function __construct() {
        $reflection = new ReflectionClass($this);
        $tableName = $reflection->getAttributes(TableName::class);
        if (count($tableName) != 1) {
            throw new InvalidSetupException();
        }

        $this->tbl_name = $tableName[0]->newInstance()->name;
        $this->tbl_alias = AliasFactory::getAlias();

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $type = $property->getType();

            if ($type instanceof ReflectionNamedType) {
                $propertyName = $property->getName();

                if ($this->isColumnType($type->getName())) {
                    $columnName = null;
                    $attributes = $property->getAttributes(TableColumn::class);
                    if (count($attributes) == 1) {
                        $columnName = $attributes[0]->newInstance()->name;
                    }

                    $property->setValue(
                        $this,
                        new Column($this->tbl_alias, $columnName ?? $propertyName)
                    );
                }
            }
        }
    }

    public function getName(): string {
        return $this->tbl_name;
    }

    private function isColumnType(string $typeName): bool {
        if ($typeName === Column::class) {
            return true;
        } elseif (class_exists($typeName)) {
            $reflectionProperty = new ReflectionClass($typeName);
            if ($reflectionProperty->isSubclassOf(Column::class)) {
                return true;
            }
        }
        return false;
    }

    final public function __toString(): string {
        return $this->tbl_name
            .(empty($this->tbl_alias) ? "" : " as `{$this->tbl_alias}`");
    }
}
