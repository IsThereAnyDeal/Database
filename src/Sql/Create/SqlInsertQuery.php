<?php
namespace IsThereAnyDeal\Database\Sql\Create;

use Ds\Set;
use Ds\Vector;
use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

class SqlInsertQuery extends SqlQuery {

    private Table $table;
    private bool $ignore = false;
    protected bool $replace = false;

    /** @var Set<string> */
    private Set $columns;

    /** @var array<string> */
    private array $updateColumns = [];

    /** @var array<array{string,string}> */
    private array $updateExpressions = [];

    /**
     * Size of the stack, when 0, stack is not automatically persisted
     */
    private int $stackSize = 0;

    /**
     * Number of currently stacked objects (because we have flat data, so can't use count())
     */
    private int $currentStacked = 0;

    /** @var Vector<scalar> */
    private Vector $values;

    /** @var ?callable(object): array<scalar> $valueMapper */
    private mixed $valueMapper = null;

    private int $insertedId = 0;
    private int $insertedRowCount = 0;

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db);
        $this->table = $table;
        $this->values = new Vector();
    }

    final public function stackSize(int $size): static {
        $this->stackSize = $size;
        return $this;
    }

    final public function columns(Column ...$columns): static {
        $this->columns = new Set(array_map(fn(Column $c) => $c->name, $columns));
        return $this;
    }

    final public function ignore(bool $value=true): static {
        $this->ignore = $value;
        return $this;
    }

    final public function onDuplicateKeyUpdate(Column ...$columns): static {
        $this->updateColumns = array_map(fn(Column $c) => $c->name, $columns);
        return $this;
    }

    final public function onDuplicateKeyExpression(Column $column, string $expression): static {
        $this->updateExpressions[] = [$column->name, $expression];
        return $this;
    }

    final public function stack(object $obj): static {
        if (is_null($this->valueMapper)) {
            $this->valueMapper = ValueMapper::getObjectValueMapper($this->columns, $obj);
        }

        $this->values->push(...call_user_func($this->valueMapper, $obj));
        $this->currentStacked++;

        if ($this->stackSize > 0 && $this->currentStacked >= $this->stackSize) {
            $this->persist();
        }
        return $this;
    }

    private function buildQuery(): string {
        $ignore = "";
        $update = "";

        $action = $this->replace
            ? "REPLACE"
            : "INSERT";

        if ($this->ignore) {
            $ignore = " IGNORE";
        }

        if (count($this->updateColumns) > 0 || count($this->updateExpressions) > 0) {
            $update = "\nON DUPLICATE KEY UPDATE ";

            $updateColumns = [];
            if (count($this->updateColumns) > 0) {
                $updateColumns = array_map(fn(string $c) => "`{$c}`=VALUES(`$c`)", $this->updateColumns);
            }
            if (count($this->updateExpressions) > 0) {
                $updateColumns = array_merge(
                    $updateColumns,
                    /** @var array{string, string} $s */
                    array_map(fn(array $s) => "`{$s[0]}`={$s[1]}", $this->updateExpressions)
                );
            }
            $update .= implode(",", $updateColumns);
        }

        $columns = "`".implode("`,`", $this->columns->toArray())."`";

        $valueListTemplate = ValueMapper::getParamTemplate(count($this->columns));
        $values = $valueListTemplate.str_repeat(",\n{$valueListTemplate}", $this->currentStacked-1);

        return "{$action}{$ignore} INTO `{$this->table->getName()}` ({$columns})\nVALUES {$values}{$update}";
    }

    final public function persist(?object $obj=null): static {
        if (count($this->values) == 0 && is_null($obj)) {
            return $this;
        }

        if (!is_null($obj)) {
            $this->stack($obj);
        }

        $statement = $this->prepare($this->buildQuery(), $this->values->toArray());
        $this->execute($statement);
        $this->insertedId = $this->getLastInsertedId();
        $this->insertedRowCount += $statement->rowCount();
        $this->clear();

        return $this;
    }

    public function getInsertedId(): int {
        return $this->insertedId;
    }

    public function getInsertedRowCount(): int {
        return $this->insertedRowCount;
    }

    public function clear(): void {
        $this->values->clear();
        $this->currentStacked = 0;
    }

    public function reset(): void {
        $this->clear();
        $this->insertedId = 0;
        $this->insertedRowCount = 0;
        $this->valueMapper = null;
    }
}
