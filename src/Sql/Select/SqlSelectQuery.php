<?php
namespace IsThereAnyDeal\Database\Sql\Select;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use PDO;

class SqlSelectQuery extends SqlQuery {

    private string $userQuery;
    private string $query;

    /** @var list<scalar> */
    private array $params = [];

    public function __construct(DbDriver $db, string $query) {
        $this->checkQueryStartsWith($query, "SELECT");
        parent::__construct($db);

        $this->userQuery = $query;
        $this->query = $query;
    }

    /**
     * @param array<string, scalar|scalar> ...$values
     * @return SqlSelectQuery
     * @throws SqlException
     * @throws MissingParameterException
     */
    final public function params(array ...$values): self {
        $params = new ParamParser($this->userQuery, ...$values);
        $this->params = $params->getValues();
        $this->query = $params->getQuery();
        return $this;
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
        $statement = $this->prepare($this->query, $this->params);
        $statement->setFetchMode(PDO::FETCH_OBJ);
        $this->execute($statement);

        $count = $statement->rowCount();
        if ($count == 0) {
            $data = new \EmptyIterator();
        } else {
            /** @var \Traversable<object> */
            $data = $statement;
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
     * @param ?array<string, scalar|scalar> $params
     * @return scalar|null
     * @throws MissingParameterException
     * @throws SqlException
     * @throws InvalidParamTypeException
     */
    final public function fetchValue(?array $params=null) {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->params);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $this->execute($statement);

        $result = $statement->fetch();
        return $result === false ? null : $result[0]; // @phpstan-ignore-line
    }

    /**
     * @param ?array<string, scalar|scalar> $params
     * @return array<scalar>
     * @throws SqlException
     */
    final public function fetchValueArray(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->params);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $this->execute($statement);

        $result = [];
        foreach($statement as $a) {
            $result[] = $a[0];
        }
        /** @var array<scalar> $result */
        return $result;
    }

    /**
     * @param ?array<string, scalar|scalar> $params
     * @return array<scalar, scalar>
     * @throws SqlException
     */
    final public function fetchPairs(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->params);
        $statement->setFetchMode(PDO::FETCH_KEY_PAIR);
        $this->execute($statement);
        return $statement->fetchAll();
    }

    /**
     * @param ?array<string, scalar|scalar> $params
     * @throws SqlException
     */
    final public function exists(?array $params=null): bool {
        if (!is_null($params)) {
            $this->params($params);
        }
        return !is_null($this->fetchValue());
    }

    /**
     * @param ?array<string, scalar|scalar> $params
     * @throws SqlException
     */
    final public function notExists(?array $params=null): bool {
        return !$this->exists($params);
    }
}
