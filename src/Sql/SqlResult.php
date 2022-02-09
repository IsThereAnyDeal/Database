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
        if (is_null($this->mapper)) {
            foreach($this->data as $value) {
                $result[] = $value;
            }
        } else {
            foreach($this->data as $value) {
                $result[] = call_user_func($this->mapper, $value);
            }
        }
        return $result;
    }

    public function toMap(callable $keyGetter): array {
        $result = [];
        if (is_null($this->mapper)) {
            foreach($this->data as $value) {
                $key = call_user_func($keyGetter, $value);
                $result[$key] = $value;
            }
        } else {
            foreach($this->data as $value) {
                $obj = call_user_func($this->mapper, $value);
                $key = call_user_func($keyGetter, $value);
                $result[$key] = $obj;
            }
        }
        return $result;
    }

    public function getIterator(): iterable {
        if (is_null($this->mapper)) {
            foreach($this->data as $value) {
                yield $value;
            }
        } else {
            foreach($this->data as $value) {
                yield call_user_func($this->mapper, $value);
            }
        }
    }

    public function count() {
        // NOT PORTABLE!!
        return $this->data->rowCount();
    }
}
