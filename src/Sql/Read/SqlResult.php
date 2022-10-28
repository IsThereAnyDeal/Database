<?php
namespace IsThereAnyDeal\Database\Sql\Read;

use Countable;
use IsThereAnyDeal\Database\Exceptions\ResultsClosedException;
use IteratorAggregate;
use Traversable;

/**
 * @template T of object
 * @implements IteratorAggregate<T>
 */
class SqlResult implements IteratorAggregate, Countable
{
    /** @var ?Traversable<T> $data */
    private ?Traversable $data;
    private int $count;

    /**
     * @param Traversable<T> $data
     * @param int $count
     */
    public function __construct(Traversable $data, int $count) {
        $this->data = $data;
        $this->count = $count;
    }

    /**
     * @template TMapped
     * @param T $data
     * @param null|callable(T): TMapped $mapper
     * @return ($mapper is null ? T : TMapped)
     */
    private function getMappedValue(mixed $data, ?callable $mapper=null): mixed {
        if (is_null($mapper)) {
            return $data;
        }

        return call_user_func($mapper, $data); // @phpstan-ignore-line
    }

    private function close(): void {
        $this->data = null;
        $this->count = 0;
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return ($mapper is null ? null|T : TMapped)
     * @throws ResultsClosedException
     */
    public function getOne(?callable $mapper=null): mixed {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        foreach($this->data as $item) {
            $result = $this->getMappedValue($item, $mapper);
            $this->close();
            return $result;
        }
        return null;
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return array<($mapper is null ? T : TMapped)>
     * @throws ResultsClosedException
     */
    public function toArray(?callable $mapper=null): array {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        $result = [];
        /** @var T $value */
        foreach($this->data as $value) {
            $result[] = $this->getMappedValue($value, $mapper);
        }
        $this->close();
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param callable(T): array{TKey,TValue} $mapper
     * @return array<TKey,TValue>
     * @throws ResultsClosedException
     */
    public function toMap(callable $mapper): array {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        $result = [];
        foreach($this->data as $value) {
            /**
             * @var TKey $k
             * @var TValue $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k] = $v;
        }
        $this->close();
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param callable(T): array{TKey,TValue} $mapper
     * @return array<TKey, array<TValue>>
     * @throws ResultsClosedException
     */
    public function toGroups(callable $mapper): array {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        $result = [];
        foreach($this->data as $value) {
            /**
             * @var TKey $k
             * @var TValue $v
             */
            list($k, $v) = call_user_func($mapper, $value); // @phpstan-ignore-line
            $result[$k][] = $v;
        }
        $this->close();
        return $result;
    }

    /**
     * @template TMapped
     * @param null|callable(T): TMapped $mapper
     * @return Traversable<($mapper is null ? null|T : TMapped)>
     * @throws ResultsClosedException
     */
    public function iterator(?callable $mapper=null) {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        /** @var T $value */
        foreach($this->data as $value) {
            yield $this->getMappedValue($value, $mapper);
        }
        $this->close();
    }

    /**
     * @return Traversable<T>
     * @throws ResultsClosedException
     */
    public function getIterator(): Traversable {
        if (is_null($this->data)) {
            throw new ResultsClosedException();
        }

        /** @var T $value */
        foreach($this->data as $value) {
            yield $value;
        }
        $this->close();
    }

    public function count(): int {
        return $this->count;
    }
}
