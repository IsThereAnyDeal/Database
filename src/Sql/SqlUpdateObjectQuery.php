<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

class SqlUpdateObjectQuery extends SqlQuery {

    private Table $table;

    /** @var Column[] */ private array $columns;
    /** @var Column[] */ private array $whereColumns;

    private string $whereSql;
    private array $whereParams;
    private array $values;

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db);
        $this->table = $table;
    }

    final public function columns(Column ...$columns): self {
        $this->columns = $columns;
        return $this;
    }

    final public function where(Column ...$columns): self {
        if (isset($this->whereSql)) {
            throw new SqlException();
        }
        $this->whereColumns = $columns;
        return $this;
    }

    /**
     * @throws MissingParameterException
     * @throws SqlException
     */
    final public function whereSql(string $sql, array $params=[]): self {
        if (isset($this->whereColumns)) {
            throw new SqlException();
        }

        $params = new Params($sql, $params);
        $this->whereParams = $params->getParams();
        $this->whereSql = $params->getQuery();
        return $this;
    }

    private function prepare(): void {
        $columns = implode(", ", array_map(fn($column) => "`{$column->name}`=?", $this->columns));

        $query = "UPDATE {$this->table->name}
                  SET $columns
                  WHERE $this->whereSql";

        $this->statement = $this->db->prepare($query);
    }

    final public function values(IInsertable $obj): self {
        $this->values = [];
        foreach($this->columns as $column) {
            $this->values[] = $obj->getDbValue($column);
        }

        if (isset($this->whereColumns)) {
            $sql = [];
            $this->whereParams = [];
            foreach($this->whereColumns as $column) {
                $sql[] = "`{$column->name}`=?";
                $this->whereParams[] = $obj->getDbValue($column);
            }
            $this->whereSql = implode(" AND ", $sql);
        }

        return $this;
    }

    final public function update(?IInsertable $obj=null): void {
        if (!is_null($obj)) {
            $this->values($obj);
        }

        $this->prepare();
        $this->execute(array_merge($this->values, $this->whereParams));

        $this->values = [];
    }

    public function getAffectedRowCount(): int {
        return $this->statement->rowCount();
    }

    public function dump(): array {
        $this->prepare();

        return [
            $this->statement->queryString,
            $this->values ?? [],
            $this->whereParams ?? []
        ];
    }
}
