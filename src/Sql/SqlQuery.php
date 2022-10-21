<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\InvalidQueryException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

abstract class SqlQuery {

    protected readonly DbDriver $driver;
    protected readonly \PDO $db;

    private PDOStatement $statement;
    private string $queryHash = "";

    private bool $profile;
    private ?LoggerInterface $logger;


    public function __construct(DbDriver $db) {
        $this->driver = $db;
        $this->db = $db->getDriver();
        $this->profile = $db->isProfile();
        $this->logger = $db->getLogger();
    }

    protected function checkQueryStartsWith(string $query, string $keyword): void {
        if (!preg_match("#^".preg_quote($keyword)."#i", $query)) {
            throw new InvalidQueryException();
        }
    }

    protected function prepare(string $query, array $values): PDOStatement {

        /**
         * Prepare new query if param counts changes,
         * otherwise we can use already prepared query
         */
        $queryHash = md5($query, true);
        if ($queryHash != $this->queryHash) {
            $this->statement = $this->db->prepare($query);
            $this->queryHash = $queryHash;
        }

        $i = 1;
        foreach($values as $value) {
            $type = match(gettype($value)) {
                "boolean" => PDO::PARAM_BOOL,
                "integer" => PDO::PARAM_INT,
                "double",
                "string" => PDO::PARAM_STR,
                "NULL" => PDO::PARAM_NULL,
                default => throw new InvalidParamTypeException()
            };
            $this->statement->bindValue($i++, $value, $type);
        }

        return $this->statement;
    }

    /**
     * @param array|null $input
     * @throws SqlException
     */
    protected function execute(PDOStatement $statement): void {
        $profile = $this->profile && !is_null($this->logger);
        if ($profile) {
            $t = microtime(true);
        }

        if (!$statement->execute()) {
            $errorInfo = $statement->errorInfo();
            throw new SqlException($errorInfo[0].": ".$errorInfo[2]);
        }

        if ($profile) {
            $time = microtime(true) - $t;
            $this->logger?->info($statement->queryString, ["execution" => $time]);
        }
    }
}
