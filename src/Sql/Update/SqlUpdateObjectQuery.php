<?php
namespace IsThereAnyDeal\Database\Sql\Update;

use Ds\Set;
use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\IInsertable;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

class SqlUpdateObjectQuery extends SqlQuery {

    private Table $table;

    /** @var array<string> */
    private array $columns;

    /** @var array<string> */
    private array $whereColumns;

    private string $whereSql;

    /** @var array<scalar> */
    private array $whereValues;

    /** @var array<scalar> */
    private array $values;

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db);
        $this->table = $table;
    }

    final public function columns(Column ...$columns): self {
        $this->columns = array_map(fn(Column $c) => $c->name, $columns);
        return $this;
    }

    final public function where(Column ...$columns): self {
        if (isset($this->whereSql)) {
            throw new SqlException();
        }

        $this->whereColumns = array_map(fn(Column $c) => $c->name, $columns);
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

        $params = new ParamParser($sql, $params);
        $this->whereValues = $params->getValues();
        $this->whereSql = $params->getQuery();
        return $this;
    }

    private function values(object $obj): void {
        $mapper = ValueMapper::getObjectValueMapper(new Set($this->columns), $obj);
        $this->values = $mapper($obj);

        if (isset($this->whereColumns)) {
            $this->whereSql = implode(
                " AND ",
                array_map(fn(string $c) => "`{$c}`=?", $this->whereColumns)
            );

            $whereMapper = ValueMapper::getObjectValueMapper(new Set($this->whereColumns), $obj);
            $this->whereValues = $whereMapper($obj);
        }
    }

    final public function update(IInsertable $obj): void {
        $this->values($obj);

        $columns = implode(", ", array_map(fn($c) => "`{$c}`=?", $this->columns));

        $query = <<<SQL
            UPDATE {$this->table->getName()}
            SET {$columns}
            WHERE {$this->whereSql}
            SQL;

        $statement = $this->prepare($query, array_merge($this->values, $this->whereValues));
        $this->execute($statement);

        $this->values = [];
    }
}
