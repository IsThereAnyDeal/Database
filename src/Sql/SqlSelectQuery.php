<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;
use PDO;
use stdClass;

class SqlSelectQuery extends SqlRawQuery {

    private function bindParams(): void {
        $i = 1;
        foreach($this->params as $param) {
            $type = PDO::PARAM_STR;
            if (is_int($param)) {
                $type = PDO::PARAM_INT;
            }
            $this->statement->bindValue($i++, $param, $type);
        }
    }

    /**
     * @template T
     * @param class-string<T>|null $className
     * @param mixed ...$constructorArgs
     * @return SqlResult<($className is null ? object : T)>
     * @throws SqlException
     */
    final public function fetch(?string $className=null, ...$constructorArgs): SqlResult {
        if (is_null($className)) {
            $this->statement->setFetchMode(PDO::FETCH_OBJ);
        } else {
            $this->statement->setFetchMode(PDO::FETCH_CLASS, $className, $constructorArgs);
        }
        $this->bindParams();
        $this->execute();

        return new SqlResult($this->statement);
    }

    /**
     * @template T
     * @param ?class-string<T> $className
     * @return T | stdClass | null
     * @throws SqlException
     */
    final public function fetchOne(?string $className=null) {
        if (is_null($className)) {
            $this->statement->setFetchMode(PDO::FETCH_OBJ);
        } else {
            $this->statement->setFetchMode(PDO::FETCH_CLASS, $className);
        }
        $this->bindParams();
        $this->execute();
        $result = $this->statement->fetch();
        return $result === false ? null : $result;
    }

    /**
     * @param array|null $params
     * @return mixed
     * @throws SqlException
     */
    final public function fetchValue(?array $params=null) {
        if (!is_null($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_NUM);
        $this->bindParams();
        $this->execute();
        $result = $this->statement->fetch();
        return $result === false ? null : $result[0];
    }

    /**
     * @param ?array $params
     * @return array
     * @throws SqlException
     */
    final public function fetchValueArray(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_NUM);
        $this->bindParams();
        $this->execute();

        $result = [];
        foreach($this->statement as $a) {
            $result[] = $a[0];
        }
        return $result;
    }

    /**
     * @throws SqlException
     */
    final public function fetchPairs(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_KEY_PAIR);
        $this->bindParams();
        $this->execute();

        return $this->statement->fetchAll();
    }

    /**
     * @throws SqlException
     */
    final public function exists(?array $params=null): bool {
        if (!is_null($params)) {
            $this->params($params);
        }
        return !is_null($this->fetchOne());
    }

    /**
     * @throws SqlException
     */
    final public function notExists(?array $params=null): bool {
        return !$this->exists($params);
    }
}
