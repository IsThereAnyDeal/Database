<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;

abstract class SqlQuery {

    protected \PDO $db;
    protected \PDOStatement $statement;

    private bool $profile;

    public function __construct(DbDriver $db) {
        $this->db = $db->getDriver();
        $this->profile = $db->isProfile();
    }

    /**
     * @param array|null $input
     * @throws SqlException
     */
    protected function execute(?array $input=null): void {
        if ($this->profile) {
            $t = microtime(true);
        }

        if (!$this->statement->execute($input)) {
            $errorInfo = $this->statement->errorInfo();
            throw new SqlException($errorInfo[0].": ".$errorInfo[2]);
        }

        if ($this->profile) {
            $time = microtime(true) - $t;
            $dump = $this->dump();
            $dump[] = debug_backtrace();
            \Log::logger("db.profile")->info($time, $dump);
        }
    }

    public function dump(): array {
        ob_start();
        $this->statement->debugDumpParams();
        $dump = preg_replace("#\n#", "\\n", ob_get_clean());
        return [$dump];
    }
}
