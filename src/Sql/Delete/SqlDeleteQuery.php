<?php
namespace IsThereAnyDeal\Database\Sql\Delete;

use BackedEnum;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;

class SqlDeleteQuery extends SqlQuery {

    private string $userQuery;

    public function __construct(DbDriver $db, string $query) {
        $this->checkQueryStartsWith($query, "DELETE");
        parent::__construct($db);

        $this->userQuery = $query;
    }

    /**
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return int
     * @throws InvalidParamTypeException
     * @throws MissingParameterException
     * @throws SqlException
     */
    final public function delete(array $params=[]): int {
        $parser = new ParamParser($this->userQuery, $params);
        $values = $parser->getValues();
        $query = $parser->getQuery();

        $statement = $this->prepare($query, $values);
        $this->execute($statement);

        return $statement->rowCount();
    }

}
