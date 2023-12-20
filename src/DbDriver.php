<?php
namespace IsThereAnyDeal\Database;

use IsThereAnyDeal\Database\Data\ObjectBuilder;
use IsThereAnyDeal\Database\Enums\EInnoDbIsolationLevel;
use IsThereAnyDeal\Database\Sql\Create\SqlInsertQuery;
use IsThereAnyDeal\Database\Sql\Create\SqlReplaceQuery;
use IsThereAnyDeal\Database\Sql\Delete\SqlDeleteQuery;
use IsThereAnyDeal\Database\Sql\Raw\SqlRawQuery;
use IsThereAnyDeal\Database\Sql\Read\SqlSelectQuery;
use IsThereAnyDeal\Database\Sql\Update\SqlUpdateObjectQuery;
use IsThereAnyDeal\Database\Sql\Update\SqlUpdateQuery;
use IsThereAnyDeal\Database\Tables\Table;
use IsThereAnyDeal\Interfaces\Profiling\ProfilerInterface;
use Psr\Log\LoggerInterface;

class DbDriver
{
    private readonly \PDO $db;
    private readonly ObjectBuilder $objectBuilder;

    private ?ProfilerInterface $profiler = null;
    private ?LoggerInterface $queryLogger = null;

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->objectBuilder = new ObjectBuilder();
    }

    public function getDriver(): \PDO {
        return $this->db;
    }

    public function getObjectBuilder(): ObjectBuilder {
        return $this->objectBuilder;
    }

    public function getProfiler(): ?ProfilerInterface {
        return $this->profiler;
    }

    public function setProfiler(?ProfilerInterface $profiler): self {
        $this->profiler = $profiler;
        return $this;
    }

    public function getQueryLogger(): ?LoggerInterface {
        return $this->queryLogger;
    }

    public function setQueryLogger(?LoggerInterface $queryLogger): self {
        $this->queryLogger = $queryLogger;
        return $this;
    }

    public function begin(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollback(): bool {
        return $this->db->rollBack();
    }

    public function inTransaction(): bool {
        return $this->db->inTransaction();
    }

    public function setSessionIsolationLevel(EInnoDbIsolationLevel $isolationLevel): void {
        $this->db->query("SET SESSION TRANSACTION ISOLATION LEVEL ".$isolationLevel->value)
            ->execute();
    }

    public function select(string $query): SqlSelectQuery {
        return new SqlSelectQuery($this, $query);
    }

    public function update(string $query): SqlUpdateQuery {
        return new SqlUpdateQuery($this, $query);
    }

    public function updateObj(Table $table): SqlUpdateObjectQuery {
        return new SqlUpdateObjectQuery($this, $table);
    }

    /**
     * @param Table $table
     * @return SqlInsertQuery
     */
    public function insert(Table $table): SqlInsertQuery {
        return new SqlInsertQuery($this, $table);
    }

    /**
     * @param Table $table
     * @return SqlReplaceQuery<object>
     */
    public function replace(Table $table): SqlReplaceQuery {
        return new SqlReplaceQuery($this, $table);
    }

    public function delete(string $query): SqlDeleteQuery {
        return new SqlDeleteQuery($this, $query);
    }

    public function raw(string $query): SqlRawQuery {
        return new SqlRawQuery($this, $query);
    }
}
