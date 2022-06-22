<?php
namespace IsThereAnyDeal\Database\Sql;

use Countable;
use IteratorAggregate;
use PDOStatement;

class SqlResult implements IteratorAggregate, Countable
{
    private PDOStatement $data;

    /** @var callable|null $mapper */
    private $mapper = null;

    public function __construct(PDOStatement $data) {
        $this->data = $data;
    }

    /**
     * @param callable(object): mixed $map
     * @return static
     */
    public function map(callable $map): self {
        $this->mapper = $map;
        return $this;
    }

    public function getOne() {
        $data = $this->data->fetch();
        if ($data === false) {
            return null;
        }

        return is_null($this->mapper)
            ? $data
            : call_user_func($this->mapper, $data);
    }

    public function toArray(): array {
        $result = [];
        foreach($this->data as $value) {
            $result[] = is_null($this->mapper)
                ? $value
                : call_user_func($this->mapper, $value);
        }
        return $result;
    }

    /**
     * @param callable(object): array-key $keyGetter
     * @return array
     */
    public function toMap(callable $keyGetter): array {
        $result = [];
        foreach($this->data as $value) {
            $key = call_user_func($keyGetter, $value);
            $result[$key] = is_null($this->mapper)
                ? $value
                : call_user_func($this->mapper, $value);
        }
        return $result;
    }

    /**
     * @param callable(object): array-key $groupParamGetter
     * @return array[][]
     */
    public function toGroups(callable $groupParamGetter): array {
        $result = [];
        foreach($this->data as $value) {
            $key = call_user_func($groupParamGetter, $value);
            if (!isset($result[$key])) {
                $result[$key] = [];
            }

            $result[$key][] = is_null($this->mapper)
                ? $value
                : call_user_func($this->mapper, $value);
        }
        return $result;
    }

    public function getIterator(): iterable {
        foreach($this->data as $value) {
            yield is_null($this->mapper)
                ? $value
                : call_user_func($this->mapper, $value);
        }
    }

    public function count() {
        // NOT PORTABLE!!
        return $this->data->rowCount();
    }
}
