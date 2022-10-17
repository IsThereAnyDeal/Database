<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;
use PDO;

/**
 * @phpstan-type ParamsMapType array<string, scalar|scalar[]>
 */
class SqlSelectQuery extends SqlRawQuery {

    /**
     * @throws InvalidParamTypeException
     */
    private function bindParams(): void {
        $i = 1;
        foreach($this->params as $param) {
            $type = match(gettype($param)) {
                "boolean" => PDO::PARAM_BOOL,
                "integer" => PDO::PARAM_INT,
                "double",
                "string" => PDO::PARAM_STR,
                "NULL" => PDO::PARAM_NULL,
                default => throw new InvalidParamTypeException()
            };
            $this->statement->bindValue($i++, $param, $type);
        }
    }

    /**
     * @template T of object
     * @param class-string<T>|null $className
     * @param mixed ...$constructorArgs
     * @return SqlResult<T>
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

        /** @var SqlResult<T> $result */
        $result = new SqlResult($this->statement);
        return $result;
    }

    /**
     * @param array $params
     * @param ParamsMapType $params
     * @return scalar|null
     * @throws SqlException
     */
    final public function fetchValue(array $params=[]) {
        if (!empty($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_NUM);
        $this->bindParams();
        $this->execute();
        $result = $this->statement->fetch();
        return $result === false ? null : $result[0]; // @phpstan-ignore-line
    }

    /**
     * @param ParamsMapType $params
     * @return array<scalar>
     * @throws SqlException
     */
    final public function fetchValueArray(array $params=[]): array {
        if (!empty($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_NUM);
        $this->bindParams();
        $this->execute();

        $result = [];
        foreach($this->statement as $a) {
            $result[] = $a[0];
        }
        /** @var array<scalar> $result */
        return $result;
    }

    /**
     * @param ParamsMapType $params
     * @return array<scalar, scalar>
     * @throws SqlException
     */
    final public function fetchPairs(array $params=[]): array {
        if (!empty($params)) {
            $this->params($params);
        }
        $this->statement->setFetchMode(PDO::FETCH_KEY_PAIR);
        $this->bindParams();
        $this->execute();

        return $this->statement->fetchAll();
    }

    /**
     * @param ParamsMapType $params
     * @throws SqlException
     */
    final public function exists(array $params=[]): bool {
        if (!empty($params)) {
            $this->params($params);
        }
        return !is_null($this->fetchValue());
    }

    /**
     * @param ParamsMapType $params
     * @throws SqlException
     */
    final public function notExists(array $params=[]): bool {
        return !$this->exists($params);
    }
}
