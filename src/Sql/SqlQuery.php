<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\InvalidQueryException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Interfaces\Profiling\ProfilerInterface;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;

abstract class SqlQuery {

    protected readonly DbDriver $driver;
    private readonly \PDO $db;

    private PDOStatement $statement;
    private string $query = "";

    private readonly ?ProfilerInterface $profiler;
    private readonly ?LoggerInterface $queryLogger;

    public function __construct(DbDriver $db) {
        $this->driver = $db;
        $this->db = $db->getDriver();
        $this->profiler = $db->getProfiler();
        $this->queryLogger = $db->getQueryLogger();
    }

    protected function checkQueryStartsWith(string $query, string $keyword): void {
        if (!preg_match("#^".preg_quote($keyword)."#i", $query)) {
            throw new InvalidQueryException();
        }
    }

    /**
     * @param string $query
     * @param array<null|scalar> $values
     * @return PDOStatement
     * @throws InvalidParamTypeException
     */
    protected function prepare(string $query, array $values): PDOStatement {

        /**
         * Prepare new query if param counts changes,
         * otherwise we can use already prepared query
         */
        if ($query != $this->query) {
            $this->statement = $this->db->prepare($query);
            $this->query = $query;
        }

        $i = 1;
        foreach($values as $value) {
            $type = match(gettype($value)) {
                "boolean" => PDO::PARAM_BOOL,
                "integer" => PDO::PARAM_INT,
                "double",
                "string" => PDO::PARAM_STR,
                "NULL" => PDO::PARAM_NULL, // @phpstan-ignore-line
                default => throw new InvalidParamTypeException()
            };
            $this->statement->bindValue($i++, $value, $type);
        }

        return $this->statement;
    }

    /**
     * @throws SqlException
     */
    protected function execute(PDOStatement $statement): void {

        $span = null;
        if (!is_null($this->profiler)) {
            $query = preg_replace("#\((?:\?\s*,\s*)+\?\)#", "(?...)", $statement->queryString);

            $span = $this->profiler
                ->createContext()
                ->setOp("db.query")
                ->setDescription($query)
                ->setData(["db.system" => "mysql"])
                ->start();
        }

        if (!$statement->execute()) {
            $errorInfo = $statement->errorInfo();
            throw new SqlException($errorInfo[0].": ".$errorInfo[2]);
        }

        if (!is_null($span)) {
            $this->profiler?->finish($span);
        }

        if (!is_null($this->queryLogger)) {
            ob_start();
            $statement->debugDumpParams();
            $debug = ob_get_clean();
            if ($debug !== false && preg_match("#Sent SQL: \[\d+] (.+)?\\nParams#s", $debug, $m)) {
                $sentSql = preg_replace("#\r#", "", $m[1]);
                $this->queryLogger->debug("", ["dump" => $sentSql]);
            }
        }
    }

    final protected function getLastInsertedId(): int {
        $id = $this->db->lastInsertId();
        if ($id === false) {
            return 0;
        }
        return (int)$id;
    }
}
