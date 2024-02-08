<?php
namespace IsThereAnyDeal\Database\Sql\Read;

use BackedEnum;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\InvalidQueryException;
use IsThereAnyDeal\Database\Exceptions\InvalidValueTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use PDO;

class SqlSelectQuery extends SqlQuery {

    private string $userQuery;
    private string $query;

    /** @var list<null|scalar> */
    private array $values = [];

    public function __construct(DbDriver $db, string $query) {
        if (!preg_match("#^[\s(]*SELECT\s#i", $query)) {
            throw new InvalidQueryException();
        }
        parent::__construct($db);

        $this->userQuery = $query;
        $this->query = $query;
    }

    /**
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> ...$values
     * @return SqlSelectQuery
     * @throws SqlException
     * @throws MissingParameterException
     */
    final public function params(array ...$values): self {
        $params = new ParamParser($this->userQuery, ...$values);
        $this->values = $params->getValues();
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
        $statement = $this->prepare($this->query, $this->values);
        $statement->setFetchMode(PDO::FETCH_OBJ);
        $this->execute($statement);

        $count = $statement->rowCount();
        if ($count == 0) {
            $data = new \EmptyIterator();
        } else {
            /** @var \Traversable<object> */
            $data = $statement; // @phpstan-ignore-line
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
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return scalar|null
     * @throws MissingParameterException
     * @throws SqlException
     * @throws InvalidParamTypeException
     */
    final public function fetchValue(?array $params=null): mixed {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->values);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $this->execute($statement);

        $result = $statement->fetch();
        return $result === false ? null : $result[0]; // @phpstan-ignore-line
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return null|string
     * @throws InvalidParamTypeException
     * @throws InvalidValueTypeException
     * @throws MissingParameterException
     * @throws SqlException
     */
    final public function fetchString(?array $params=null): ?string {
        $result = $this->fetchValue($params);
        if (is_null($result) || is_string($result)) {
            return $result;
        }
        throw new InvalidValueTypeException();
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return null|int
     * @throws InvalidParamTypeException
     * @throws InvalidValueTypeException
     * @throws MissingParameterException
     * @throws SqlException
     */
    final public function fetchInt(?array $params=null): ?int {
        $result = $this->fetchValue($params);
        if (is_null($result) || is_int($result)) {
            return $result;
        }
        throw new InvalidValueTypeException();
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return list<scalar>
     * @throws SqlException
     */
    final public function fetchValueArray(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->values);
        $statement->setFetchMode(PDO::FETCH_NUM);
        $this->execute($statement);

        $result = [];
        /** @var array<scalar> $a */
        foreach($statement as $a) {
            $result[] = $a[0];
        }
        $statement->closeCursor();
        return $result;
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return array<scalar, scalar>
     * @throws SqlException
     */
    final public function fetchPairs(?array $params=null): array {
        if (!is_null($params)) {
            $this->params($params);
        }

        $statement = $this->prepare($this->query, $this->values);
        $statement->setFetchMode(PDO::FETCH_KEY_PAIR);
        $this->execute($statement);
        return $statement->fetchAll();
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @throws SqlException
     */
    final public function exists(?array $params=null): bool {
        if (!is_null($params)) {
            $this->params($params);
        }
        return !is_null($this->fetchValue());
    }

    /**
     * @param ?array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @throws SqlException
     */
    final public function notExists(?array $params=null): bool {
        return !$this->exists($params);
    }
}
