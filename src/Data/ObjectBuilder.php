<?php
namespace IsThereAnyDeal\Database\Data;

use Ds\Map;
use Ds\Set;
use Generator;
use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Data\Structs\SClassDescriptor;
use IsThereAnyDeal\Database\Data\Structs\SColumnDescriptor;
use IsThereAnyDeal\Database\Data\Structs\SColumnRecipe;
use IsThereAnyDeal\Database\Enums\EConstructionType;
use IsThereAnyDeal\Database\Exceptions\InvalidDeserializerException;
use ReflectionClass;
use ReflectionException;

// TODO better name
class ObjectBuilder
{
    private const DefaultConstructionType = EConstructionType::AfterFetch;

    /** @var Map<class-string, SClassDescriptor>*/
    private readonly Map $cache;

    private bool $enableCaching = false;

    public function __construct() {
        $this->cache = new Map();
    }

    public function toggleCaching(bool $enable): self {
        $this->enableCaching = $enable;

        if (!$enable) {
            $this->cache->clear();
        }
        return $this;
    }

    /**
     * @template T of object
     * @param class-string<T>|T $classOrObject
     * @return SClassDescriptor
     * @throws ReflectionException
     */
    private function parseClass(string|object $classOrObject): SClassDescriptor {

        if (!is_object($classOrObject) && $this->cache->hasKey($classOrObject)) {
            return $this->cache->get($classOrObject);
        }

        $class = new ReflectionClass($classOrObject);

        $properties = [];
        foreach($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $attributes = $property->getAttributes(Column::class);

            $dbColumName = null;
            $deserializer = null;
            if (count($attributes) > 0) {
                $attribute = $attributes[0]; // attribute is not repeatable

                /** @var Column $column */
                $column = $attribute->newInstance();
                $dbColumName = $column->name;
                $deserializer = $column->deserializer;
            }

            $properties[] = new SColumnDescriptor(
                $property,
                $dbColumName ?? $property->getName(),
                $deserializer // @phpstan-ignore-line
            );
        }

        /** @var array<\ReflectionAttribute<Construction>> $ctorAttributes */
        $ctorAttributes = $class->getAttributes(Construction::class);
        $constructionType = empty($ctorAttributes)
            ? self::DefaultConstructionType
            : $ctorAttributes[0]->newInstance()->type;

        $result = new SClassDescriptor(
            $class,
            $constructionType,
            $properties
        );

        if ($this->enableCaching && !is_object($classOrObject)) {
            $this->cache->put($classOrObject, $result);
        }
        return $result;
    }

    /**
     * @param array<SColumnDescriptor> $properties
     * @param Set<string> $dataset
     * @return list<SColumnRecipe>
     * @throws InvalidDeserializerException
     */
    private function getRecipe(array $properties, Set $dataset): array {

        $recipe = [];
        foreach($properties as $cp) {
            if (is_array($cp->column)) {
                $dbColumns = $cp->column;

                if (!$dataset->contains(...$dbColumns)) {
                    continue;
                }

                $valueSetter = fn(object $o) => call_user_func_array(
                    $cp->deserializer, // @phpstan-ignore-line
                    array_map(fn($prop) => $o->{$prop}, $dbColumns)
                );

                $nullable = $cp->property->getType()?->allowsNull();
                if ($nullable) {
                    $setter = fn(object $o) => count(array_filter($dbColumns, fn($prop) => is_null($o->{$prop}))) > 0
                        ? null
                        : ($valueSetter)($o);
                } else {
                    $setter = $valueSetter;
                }
            } else {
                $dbColumn = $cp->column;

                if (!$dataset->contains($dbColumn)) {
                    continue;
                }

                if (!is_null($cp->deserializer)) {
                    if (is_callable($cp->deserializer)) {
                        $valueSetter = fn(object $o) => ($cp->deserializer)($o->{$dbColumn});

                        $nullable = $cp->property->getType()?->allowsNull();
                        $setter = $nullable
                            ? (fn(object $o) => is_null($o->{$dbColumn}) ? null : $valueSetter($o))
                            : $valueSetter;
                    } elseif (is_array($cp->deserializer) && count($cp->deserializer) == 2) {
                        list($className, $method) = $cp->deserializer;

                        if ($cp->property->getType() instanceof \ReflectionNamedType
                         && $cp->property->getType()->getName() == $className
                         && is_string($method)
                        ) {
                            $setter = function(object $o) use($className, $method, $dbColumn) {
                                $value = $o->{$dbColumn};
                                return is_null($value)
                                    ? null
                                    : (new $className())->$method($value);
                            };
                        } else {
                            throw new InvalidDeserializerException();
                        }
                    } else {
                        throw new InvalidDeserializerException();
                    }
                } else {
                    $setter = $dbColumn;
                    if ($cp->property->getType() instanceof \ReflectionNamedType) {
                        /** @var \ReflectionNamedType $type */
                        $type = $cp->property->getType();
                        $typeName = $type->getName();

                        if (is_subclass_of($typeName, \BackedEnum::class)) {
                            if ($type->allowsNull()) {
                                $setter = function(object $o) use($dbColumn, $typeName) {
                                    $value = $o->{$dbColumn};
                                    return is_null($value)
                                        ? null
                                        : ($typeName)::tryFrom($o->{$dbColumn});
                                };
                            } else {
                                $setter = fn(object $o) => ($typeName)::from($o->{$dbColumn});
                            }
                        } elseif (class_exists($typeName)) {
                            if ($type->allowsNull()) {
                                $setter = fn(object $o) => is_null($o->{$dbColumn}) ? null : new ($typeName)($o->{$dbColumn});
                            } else {
                                $setter = fn(object $o) => new ($typeName)($o->{$dbColumn});
                            }
                        }
                    }
                }
            }

            $recipe[] = new SColumnRecipe($cp->property, $setter); // @phpstan-ignore-line
        }

        return $recipe;
    }

    /**
     * Build objects from raw database data
     *
     * @template T of object
     * @param class-string<T>|T $classOrObject
     * @param \Traversable<object> $data
     * @param array<mixed> ...$constructorParams
     * @return Generator<T>
     * @throws ReflectionException
     */
    public function build(string|object $classOrObject, \Traversable $data, mixed ...$constructorParams): Generator {
        $classDescriptor = $this->parseClass($classOrObject);

        /** @var ReflectionClass<T> $class */
        $class = $classDescriptor->class;

        /** @var array<SColumnDescriptor> $properties */
        $properties = $classDescriptor->columns;
        $constructionType = $classDescriptor->construction;

        $buildRecipe = true;
        foreach($data as $row) {
            if ($buildRecipe) {
                $dataset = new Set(array_keys(get_object_vars($row)));
                $recipe = $this->getRecipe($properties, $dataset);
                $buildRecipe = false;
            }

            $instance = $class->newInstanceWithoutConstructor();

            if ($constructionType == EConstructionType::BeforeFetch) {
                $class->getConstructor()?->invokeArgs($instance, $constructorParams);
            }

            /** @var list<SColumnRecipe> $recipe */
            foreach($recipe as $item) {
                $item->property->setValue($instance,
                    is_string($item->setter)
                        ? $row->{$item->setter}
                        : call_user_func($item->setter, $row)); // @phpstan-ignore-line TODO what's this error, looks scary
            }

            if ($constructionType == EConstructionType::AfterFetch) {
                $class->getConstructor()?->invokeArgs($instance, $constructorParams);
            }

            yield $instance;
        }
    }
}
