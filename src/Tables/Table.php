<?php
namespace IsThereAnyDeal\Database\Tables;

use IsThereAnyDeal\Database\Attributes\TableColumn;
use IsThereAnyDeal\Database\Attributes\TableName;
use IsThereAnyDeal\Database\Exceptions\InvalidSetupException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

abstract class Table
{
    public readonly string $__name__;
    public readonly string $__alias__;

    public function __construct() {
        $reflection = new ReflectionClass($this);
        $tableName = $reflection->getAttributes(TableName::class);
        if (count($tableName) != 1) {
            throw new InvalidSetupException();
        }

        $this->__name__ = $tableName[0]->newInstance()->name;
        $this->__alias__ = AliasFactory::getAlias();

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
                        new Column($this, $columnName ?? $propertyName)
                    );
                }
            }
        }
    }

    public function getName(): string {
        return $this->__name__;
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
        return "`{$this->__name__}`"
            .(empty($this->__alias__) ? "" : " as `{$this->__alias__}`");
    }
}
