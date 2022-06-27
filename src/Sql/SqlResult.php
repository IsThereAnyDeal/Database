<?php
namespace IsThereAnyDeal\Database\Sql;

use Countable;
use IteratorAggregate;
use PDOStatement;
use Traversable;

/**
 * @template T
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
     * @return V|null
     */
    public function getOne(?callable $mapper=null) {
        $data = $this->data->fetch();
        if ($data === false) {
            return null;
        }

        return is_null($mapper)
            ? $data
            : call_user_func($mapper, $data);
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @return array<($mapper is null ? V : T)>
     */
    public function toArray(?callable $mapper=null): array {
        $result = [];
        foreach($this->data as $value) {
            $result[] = is_null($mapper)
                ? $value
                : call_user_func($mapper, $value);
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
            list($k, $v) = call_user_func($mapper, $value);
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
            list($k, $v) = call_user_func($mapper, $value);
            if (!isset($result[$k])) {
                $result[$k] = [];
            }
            $result[$k][] = $v;
        }
        return $result;
    }

    /**
     * @template V
     * @param null|callable(T): V $mapper
     * @return Traversable<($mapper is null ? V : T)>
     */
    public function iterator(?callable $mapper=null) {
        foreach($this->data as $value) {
            yield is_null($mapper)
                ? $value
                : call_user_func($mapper, $value);
        }
    }

    /** @return Traversable<T> */
    public function getIterator(): Traversable {
        foreach($this->data as $value) {
            yield $value;
        }
    }

    public function count(): int {
        // NOT PORTABLE!!
        return $this->data->rowCount();
    }
}
