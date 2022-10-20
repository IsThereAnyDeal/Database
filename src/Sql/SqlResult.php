<?php
namespace IsThereAnyDeal\Database\Sql;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template T of object
 * @implements IteratorAggregate<T>
 */
class SqlResult implements IteratorAggregate, Countable
{
    /**
     * @param Traversable<T> $data
     * @param int $count
     */
    public function __construct(
        private readonly Traversable $data,
        private readonly int         $count
    ) {}

    /**
     * @template TMapped
     * @param T $data
     * @param null|callable(T): TMapped $mapper
     * @return T|TMapped
     */
    private function getMappedValue(mixed $data, ?callable $mapper=null): mixed {
        if (is_null($mapper)) {
            return $data;
        }

        return call_user_func($mapper, $data); // @phpstan-ignore-line
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return null|T|TMapped
     */
    public function getOne(?callable $mapper=null) {
        foreach($this->data as $item) {
            return $this->getMappedValue($item, $mapper);
        }
        return null;
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return array<T|TMapped>
     */
    public function toArray(?callable $mapper=null): array {
        $result = [];
        /** @var T $value */
        foreach($this->data as $value) {
            $result[] = $this->getMappedValue($value, $mapper);
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param callable(T): array{TKey,TValue} $mapper
     * @return array<TKey,TValue>
     */
    public function toMap(callable $mapper): array {
        $result = [];
        foreach($this->data as $value) {
            /**
             * @var TKey $k
             * @var TValue $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param callable(T): array{TKey,TValue} $mapper
     * @return array<TKey, array<TValue>>
     */
    public function toGroups(callable $mapper): array {
        $result = [];
        foreach($this->data as $value) {
            /**
             * @var TKey $k
             * @var TValue $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k][] = $v;
        }
        return $result;
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return Traversable<T|TMapped>
     */
    public function iterator(?callable $mapper=null) {
        /** @var T $value */
        foreach($this->data as $value) {
            yield $this->getMappedValue($value, $mapper);
        }
    }

    /** @return Traversable<T> */
    public function getIterator(): Traversable {
        /** @var T $value */
        foreach($this->data as $value) {
            yield $value;
        }
    }

    public function count(): int {
        return $this->count;
    }
}
