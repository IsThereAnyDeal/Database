<?php
namespace IsThereAnyDeal\Database\Sql\Data;

use Ds\Map;
use Ds\Set;
use Generator;
use IsThereAnyDeal\Database\Attributes\Column;
use ReflectionClass;
use ReflectionException;

class ObjectBuilder
{
    /** @var Map<array{ReflectionClass, array<SColumnProperty>}>*/
    private readonly Map $cache;

    public function __construct() {
        $this->cache = new Map();
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return array<array{ReflectionClass, array<SColumnProperty>}>
     * @throws ReflectionException
     */
    private function parseClass(string $className): array {

        if ($this->cache->hasKey($className)) {
            return $this->cache->get($className);
        }

        $class = new ReflectionClass($className);

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

                if (is_array($dbColumName) && !is_callable($deserializer)) {
                    // throw new
                }
            }

            $properties[] = new SColumnProperty(
                $property,
                $dbColumName ?? $property->getName(),
                $deserializer
            );
        }

        $result = [$class, $properties];

        $this->cache->put($className, $result);
        return $result;
    }

    /**
     * @param array<SColumnProperty> $properties
     * @param Set<string> $dataset
     * @return array<SColumnRecipe>
     */
    private function getRecipe(array $properties, Set $dataset): array {

        $recipe = [];
        foreach($properties as $cp) {
            if (is_array($cp->column)) {
                $dbColumns = $cp->column;

                if (!$dataset->contains(...$dbColumns)) {
                    continue;
                }

                $setter = fn(object $o) => call_user_func(
                    $cp->deserializer, // @phpstan-ignore-line
                    $o, $dbColumns
                );
            } else {
                $dbColumn = $cp->column;

                if (!$dataset->contains($dbColumn)) {
                    continue;
                }

                $setter = is_callable($cp->deserializer)
                    ? fn(object $o) => call_user_func($cp->deserializer, $o->{$dbColumn})
                    : $dbColumn;
            }

            $recipe[] = new SColumnRecipe($cp->property, $setter);
        }

        return $recipe;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param iterable<object> $data
     * @return Generator<T>
     * @throws ReflectionException
     */
    public function build(string $className, iterable $data): iterable {
        list($class, $properties) = $this->parseClass($className);
        $dataset = new Set(array_keys(get_object_vars(current($data))));

        $recipe = $this->getRecipe($properties, $dataset);

        foreach($data as $row) {
            $instance = $class->newInstanceWithoutConstructor();
            foreach($recipe as $item) {
                $item->property->setValue($instance, is_string($item->setter)
                    ? $row->{$item->setter}
                    : call_user_func($item->setter, $row));
            }
            yield $instance;
        }
    }
}
