<?php

namespace IsThereAnyDeal\Database\Data;

use BackedEnum;
use Ds\Set;
use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Exceptions\InvalidValueTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingDataException;
use ReflectionClass;

class ValueMapper
{
    /**
     * Generate value mapper for object with selected columns
     *
     * @template T of object
     * @param Set<string> $columnSet
     * @param class-string<T>|T $obj
     * @return callable(T): array<scalar>  Mapper that generates array of scalar values to be used in PDO for queries
     * @throws \ReflectionException
     */
    public static function getObjectValueMapper(Set $columnSet, string|object $obj) {
        $class = new ReflectionClass($obj);

        /** @var array<callable(object): array<string,scalar>> $getters */
        $getters = [];

        $props = $class->getProperties();
        foreach($props as $prop) {
            $attribute = $prop->getAttributes(Column::class)[0] ?? null;

            $column = null;
            if (!is_null($attribute)) {
                /** @var Column $column */
                $column = $attribute->newInstance();
            }

            $name = $column?->name ?? $prop->getName();
            $serializer = $column?->serializer;

            if (is_array($name)) {
                $names = $name;
                $inResultSet = false;
                foreach($names as $n) {
                    if ($columnSet->contains($n)) {
                        $inResultSet = true;
                        break;
                    }
                }
                if (!$inResultSet) {
                    continue;
                }

                if (!is_null($serializer)) {
                    if (is_string($serializer) && $serializer[0] == "@") {
                        $func = substr($serializer, 1);
                        $getters[] = fn(object $obj) => array_combine($names, call_user_func([$prop->getValue($obj), $func]));
                    } else {
                        $getters[] = fn(object $obj) => array_combine($names, call_user_func($serializer, $prop->getValue($obj)));
                    }
                }
            } else {
                if (!$columnSet->contains($name)) {
                    continue;
                }

                if (!is_null($serializer)) {
                    if (is_string($serializer) && $serializer[0] == "@") {
                        $func = substr($serializer, 1);
                        $getters[] = fn(object $obj) => [$name => call_user_func([$prop->getValue($obj), $func])];
                    } else {
                        $getters[] = fn(object $obj) => [$name => call_user_func($serializer, $prop->getValue($obj))];
                    }
                } else {
                    $getters[] = fn(object $obj) => [$name => $prop->getValue($obj)];
                }
            }
        }

        return function(object $obj) use($columnSet, $getters) {
            $data = array_merge(...array_map(fn($getter) => call_user_func($getter, $obj), $getters));

            $result = [];
            foreach($columnSet as $column) {
                if (!array_key_exists($column, $data)) {
                    throw new MissingDataException("Missing data for column '$column'");
                }
                $value = $data[$column];

                if (is_scalar($value) || is_null($value)) {
                    $result[] = $value;
                } elseif ($value instanceof BackedEnum) {
                    $result[] = $value->value;
                } else {
                    throw new InvalidValueTypeException("Value of type ".gettype($value). " can't be used in SQL");
                }
            }
            return $result;
        };
    }

}