<?php
namespace IsThereAnyDeal\Database\Sql\Update;

use BackedEnum;
use Ds\Set;
use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\ImplicitFullTableUpdateException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

class SqlUpdateObjectQuery extends SqlQuery {

    private Table $table;

    /** @var Set<string> */
    private Set $columns;

    /** @var Set<string> */
    private Set $whereColumns;
    private ?ParamParser $whereExp = null;

    /** @var list<null|scalar> */
    private array $values;

    private bool $fullTableUpdate = false;

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db);
        $this->table = $table;
        $this->columns = new Set();
        $this->whereColumns = new Set();
    }

    final public function columns(Column ...$columns): self {
        $this->columns->clear();
        $this->columns->add(...array_map(fn(Column $c) => $c->name, $columns));
        return $this;
    }

    final public function where(Column ...$columns): self {
        $this->whereColumns->clear();
        $this->whereColumns->add(...array_map(fn(Column $c) => $c->name, $columns));
        return $this;
    }

    public function fullTableUpdate(bool $fullTableUpdate=true): self {
        $this->fullTableUpdate = $fullTableUpdate;
        return $this;
    }

    /**
     * @param string $sql
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     * @return SqlUpdateObjectQuery
     * @throws MissingParameterException
     * @throws SqlException
     */
    final public function whereSql(string $sql, array $params=[]): self {
        $this->whereExp = new ParamParser($sql, $params);
        return $this;
    }

    private function values(object $obj): void {
        $mapper = ValueMapper::getObjectValueMapper($this->columns, $obj);
        $this->values = $mapper($obj);
    }

    /**
     * @param object $obj
     * @return array{string, list<null|scalar>}
     * @throws \ReflectionException
     * @throws SqlException
     */
    private function getWhere(object $obj): array {
        $conditions = [];
        $values = [];

        if (count($this->whereColumns) > 0) {
            $conditions[] = implode(
                " AND ",
                array_map(fn(string $c) => "`{$c}`=?", $this->whereColumns->toArray())
            );

            $whereMapper = ValueMapper::getObjectValueMapper($this->whereColumns, $obj);
            $values = $whereMapper($obj);
        }

        if (!is_null($this->whereExp)) {
            $conditions[] = $this->whereExp->getQuery();
            $values = array_merge($values, $this->whereExp->getValues());
        }

        if (count($conditions) == 0) {
            if (!$this->fullTableUpdate) {
                throw new ImplicitFullTableUpdateException();
            }
            return ["", []];
        }

        return [
            "\nWHERE ".implode(" AND ", $conditions),
            $values
        ];
    }

    final public function update(object $obj): int {
        if (!isset($this->columns) || count($this->columns) == 0) {
            throw new SqlException();
        }

        $this->values($obj);
        $columns = implode(", ", array_map(fn($c) => "`{$c}`=?", $this->columns->toArray()));

        list($whereSql, $whereValues) = $this->getWhere($obj);

        $query = "UPDATE {$this->table}\nSET {$columns}{$whereSql}";

        $statement = $this->prepare($query, array_merge($this->values, $whereValues));
        $this->execute($statement);

        $this->values = [];

        return $statement->rowCount();
    }
}
