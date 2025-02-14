<?php
namespace IsThereAnyDeal\Database\Data;

use BackedEnum;
use Ds\Set;
use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Exceptions\InvalidSerializerException;
use IsThereAnyDeal\Database\Exceptions\InvalidValueTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingDataException;
use ReflectionClass;

// TODO better name
class ValueMapper
{
    /**
     * @param int $valueCount
     * @param int $tupleSize
     * @return string
     */
    public static function getValueTemplate(int $valueCount, int $tupleSize=1): string {
        $template = $tupleSize === 1
            ? "?"
            : "(?".str_repeat(",?", $tupleSize-1).")";

        $tuples = $valueCount / $tupleSize;
        return $tuples === 1
            ? "({$template})"
            : "($template".str_repeat(",$template", $tuples-1).")";
    }

    /**
     * Generate value mapper for object with selected columns
     *
     * @template T of object
     * @param Set<string> $columnSet
     * @param class-string<T>|T $obj
     * @return \Closure(T): list<scalar|null>  Mapper that generates array of scalar values to be used in PDO for queries
     * @throws \ReflectionException
     */
    public static function getObjectValueMapper(Set $columnSet, string|object $obj): mixed {
        $class = new ReflectionClass($obj);

        /** @var list<callable(T): array<string, null|scalar|BackedEnum>> $getters */
        $getters = [];

        $props = $class->getProperties();
        foreach($props as $prop) {
            $attribute = $prop->getAttributes(Column::class)[0] ?? null;

            $column = null;
            if (!is_null($attribute)) {
                /** @var Column $column */
                $column = $attribute->newInstance();
            }

            $name = $column->name ?? $prop->getName();
            $serializer = $column?->serializer;

            if (is_array($name)) {
                $inResultSet = false;
                foreach($name as $n) {
                    if ($columnSet->contains($n)) {
                        $inResultSet = true;
                        break;
                    }
                }
                if (!$inResultSet) {
                    continue;
                }
            } elseif (!$columnSet->contains($name)) {
                continue;
            }

            if (!is_null($serializer)) {
                if (!is_callable($serializer)) {
                    if (is_array($serializer) && count($serializer) == 2) {
                        list($className, $method) = $serializer;
                        if ($prop->getType() instanceof \ReflectionNamedType
                         && $prop->getType()->getName() === $className
                         && is_string($method)) // @phpstan-ignore-line // extra safety
                        {
                            $getters[] = function(object $obj) use($name, $prop, $method) {
                                $value = $prop->getValue($obj);

                                if (is_array($name)) {
                                    return is_null($value)
                                        ? array_combine($name, array_fill(0, count($name), null))
                                        : array_combine($name, $value->$method());
                                } else {
                                    return [
                                        $name => is_null($value)
                                            ? null
                                            : $value->$method()
                                    ];
                                }
                            };
                        } else {
                            throw new InvalidSerializerException();
                        }
                    } else {
                        throw new InvalidSerializerException();
                    }
                } else {
                    $getters[] = function(object $obj) use($name, $serializer, $prop) {
                        $value = $prop->getValue($obj);

                        if (is_array($name)) {
                            return is_null($value)
                                ? array_combine($name, array_fill(0, count($name), null))
                                : array_combine($name, $serializer($value));
                        } else {
                            return [
                                $name => is_null($value)
                                    ? null
                                    : $serializer($value)
                            ];
                        }
                    };
                }
            } else {
                if (is_array($name)) {
                    throw new InvalidSerializerException("Missing serializer for multi-column property");
                } else {
                    $propType = null;
                    if ($prop->getType() instanceof \ReflectionNamedType) {
                        $propType = $prop->getType()->getName();
                    }

                    if (!is_null($propType) && class_exists($propType) && !is_subclass_of($propType, BackedEnum::class)) {
                        $class = new ReflectionClass($propType);
                        if ($class->implementsInterface(\Stringable::class)) {
                            $getters[] = fn(object $obj) => [$name => (string)$prop->getValue($obj)]; // @phpstan-ignore-line
                        } else {
                            throw new InvalidSerializerException("Missing serializer for object property");
                        }
                    } else {
                        $getters[] = fn(object $obj) => [$name => $prop->getValue($obj)];
                    }
                }
            }
        }

        return function(object $obj) use($columnSet, $getters) {
            /** @var list<array<string, null|scalar|BackedEnum>> $values */
            $values = array_map(fn($getter) => call_user_func($getter, $obj), $getters);
            $data = array_merge(...$values);

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
