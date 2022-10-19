<?php
namespace IsThereAnyDeal\Database\Sql\Data;

use Ds\Map;
use Ds\Set;
use Generator;
use IsThereAnyDeal\Database\Attributes\Column;
use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;
use IsThereAnyDeal\Database\Sql\Exceptions\InvalidDeserializerException;
use ReflectionClass;
use ReflectionException;

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
     * @param class-string<T> $className
     * @return SClassDescriptor
     * @throws ReflectionException
     */
    private function parseClass(string $className): SClassDescriptor {

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
                    throw new InvalidDeserializerException();
                }
            }

            $properties[] = new SColumnDescriptor(
                $property,
                $dbColumName ?? $property->getName(),
                $deserializer
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

        if ($this->enableCaching) {
            $this->cache->put($className, $result);
        }
        return $result;
    }

    /**
     * @param array<SColumnDescriptor> $properties
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
     * @param array ...$constructorParams
     * @return Generator<T>
     * @throws ReflectionException
     */
    public function build(string $className, iterable $data, mixed ...$constructorParams): iterable {
        /**
         * @var ReflectionClass $class
         * @var array<SColumnDescriptor> $properties
         */
        $classDescriptor = $this->parseClass($className);
        $class = $classDescriptor->class;
        $constructionType = $classDescriptor->construction;
        $properties = $classDescriptor->columns;

        $dataset = new Set(array_keys(get_object_vars(current($data))));

        $recipe = $this->getRecipe($properties, $dataset);

        foreach($data as $row) {
            $instance = $class->newInstanceWithoutConstructor();

            if ($constructionType == EConstructionType::BeforeFetch) {
                $class->getConstructor()?->invokeArgs($instance, $constructorParams);
            }

            foreach($recipe as $item) {
                $item->property->setValue($instance, is_string($item->setter)
                    ? $row->{$item->setter}
                    : call_user_func($item->setter, $row));
            }

            if ($constructionType == EConstructionType::AfterFetch) {
                $class->getConstructor()?->invokeArgs($instance, $constructorParams);
            }

            yield $instance;
        }
    }
}
