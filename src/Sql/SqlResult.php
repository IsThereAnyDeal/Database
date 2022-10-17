<?php
namespace IsThereAnyDeal\Database\Sql;

use Countable;
use IteratorAggregate;
use PDOStatement;
use Traversable;

/**
 * @template T of object
 * @implements IteratorAggregate<T>
 */
class SqlResult implements IteratorAggregate, Countable
{
    private PDOStatement $data;

    public function __construct(PDOStatement $data) {
        $this->data = $data;
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @param T $data
     * @return T|V
     */
    private function getMappedValue(?callable $mapper=null, mixed $data): mixed {
        if (is_null($mapper)) {
            return $data;
        }

        return call_user_func($mapper, $data); // @phpstan-ignore-line
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @return null|T|V
     */
    public function getOne(?callable $mapper=null) {
        /** @var T|false $data */
        $data = $this->data->fetch();
        if ($data === false) {
            return null;
        }

        return $this->getMappedValue($mapper, $data);
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @return array<T|V>
     */
    public function toArray(?callable $mapper=null): array {
        $result = [];
        /** @var T $value */
        foreach($this->data as $value) {
            $result[] = $this->getMappedValue($mapper, $value);
        }
        return $result;
    }

    /**
     * @template K of array-key
     * @template V
     * @param callable(T): array{K,V} $mapper
     * @return array<K, V>
     */
    public function toMap(callable $mapper): array {
        $result = [];
        foreach($this->data as $value) {
            /**
             * @var K $k
             * @var V $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * @template K of array-key
     * @template V
     * @param callable(T): array{K, V} $mapper
     * @return array<K, array<V>>
     */
    public function toGroups(callable $mapper): array {
        $result = [];
        foreach($this->data as $value) {
            /**
             * @var K $k
             * @var V $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k][] = $v;
        }
        return $result;
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @return Traversable<T|V>
     */
    public function iterator(?callable $mapper=null) {
        /** @var T $value */
        foreach($this->data as $value) {
            yield $this->getMappedValue($mapper, $value);
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
        // NOT PORTABLE!!
        return $this->data->rowCount();
    }
}
