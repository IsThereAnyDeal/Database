<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;
use Psr\Log\LoggerInterface;

abstract class SqlQuery {

    protected \PDO $db;
    protected \PDOStatement $statement;

    private bool $profile;
    private ?LoggerInterface $logger;

    public function __construct(DbDriver $db) {
        $this->db = $db->getDriver();
        $this->profile = $db->isProfile();
        $this->logger = $db->getLogger();
    }

    /**
     * @param array|null $input
     * @throws SqlException
     */
    protected function execute(?array $input=null): void {
        $profile = $this->profile && !is_null($this->logger);
        if ($profile) {
            $t = microtime(true);
        }

        if (!$this->statement->execute($input)) {
            $errorInfo = $this->statement->errorInfo();
            throw new SqlException($errorInfo[0].": ".$errorInfo[2]);
        }

        if ($profile) {
            $time = microtime(true) - $t;
            $dump = $this->dump();
            $dump[] = debug_backtrace();
            $this->logger->info((string)$time, $dump);
        }
    }

    public function dump(): array {
        ob_start();
        $this->statement->debugDumpParams();
        $dump = preg_replace("#\n#", "\\n", ob_get_clean());
        return [$dump];
    }
}
