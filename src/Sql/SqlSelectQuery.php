<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
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
     * @param null|class-string<T>|T $className
     * @param mixed ...$constructorArgs
     * @return SqlResult<T>
     * @throws SqlException
     * @throws \ReflectionException
     */
    final public function fetch(null|string|object $className=null, mixed ...$constructorArgs): SqlResult {
        $this->statement->setFetchMode(PDO::FETCH_OBJ);
        $this->bindParams();
        $this->execute();

        $count = $this->statement->rowCount();
        if ($count == 0) {
            $data = new \EmptyIterator();
        } else {
            /** @var \Traversable<object> */
            $data = $this->statement;
            if (!is_null($className)) {
                $objectBuilder = $this->driver->getObjectBuilder();
                $data = $objectBuilder->build($className, $data, $constructorArgs);
            }
        }

        /** @var SqlResult<T> $result */
        $result = new SqlResult($data, $count);
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
