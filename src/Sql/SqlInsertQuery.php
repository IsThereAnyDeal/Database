<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Sql\Exceptions\NotSupportedException;
use IsThereAnyDeal\Database\Sql\Tables\Column;
use IsThereAnyDeal\Database\Sql\Tables\Table;

class SqlInsertQuery extends SqlQuery {

    private Table $table;
    /** @var Column[] */
    private array $columns;
    private bool $ignore = false;
    protected bool $replace = false;

    /** @var array<Column|array{Column, string}> */
    private array $update = [];

    /**
     * Size of the stack, when 0, stack is not automatically persisted
     */
    private int $stackSize = 0;

    /**
     * Number of currently stacked objects (because we have flat data, so can't use count())
     */
    private int $currentStacked = 0;

    private array $data = [];

    private int $preparedForCount = -1;

    private int $insertedRowCount = 0;

    public function __construct(DbDriver $db, Table $table) {
        parent::__construct($db);
        $this->table = $table;
    }

    /**
     * @return static
     */
    final public function stackSize(int $size): self {
        $this->stackSize = $size;
        return $this;
    }

    /**
     * @return static
     */
    final public function columns(Column ...$columns): self {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return static
     */
    final public function ignore(bool $value=true): self {
        $this->ignore = $value;
        return $this;
    }

    /**
     * @return static
     */
    final public function onDuplicateKeyUpdate(Column ...$columns): self {
        $this->update = array_merge($this->update, $columns);
        return $this;
    }

    /**
     * @return static
     */
    final public function onDuplicateKeyExpression(Column $column, string $expression): self {
        $this->update[] = [$column, $expression];
        return $this;
    }

    private function getValuesTemplate(int $count=1): string {
        $template = "("
            .implode(", ",
                array_fill(0, count($this->columns), "?"))
            .")";
        if ($count == 1) {
            return $template;
        }
        return implode(", ", array_fill(0, $count, $template));
    }

    private function prepare(): void {
        if ($this->preparedForCount == $this->currentStacked) {
            return;
        }

        $ignore = "";
        $update = "";
        $columns = implode(",", array_map(fn($column) => "`{$column->name}`", $this->columns));
        $values = $this->getValuesTemplate($this->currentStacked);

        $action = $this->replace
            ? "REPLACE"
            : "INSERT";

        if ($this->ignore) {
            $ignore = "IGNORE";
        }

        if (count($this->update) > 0) {
            $update = "ON DUPLICATE KEY UPDATE "
                .implode(", ",
                    array_map(function($c) {
                        if ($c instanceof Column) {
                            return "`{$c->name}`=VALUES(`{$c->name}`)";
                        /**
                         * @phpstan-ignore-next-line This just adds additional safety for future,
                         * even though right now $c[0] is always Column
                         */
                        } elseif (is_array($c) && $c[0] instanceof Column) {
                            return "`{$c[0]->name}`={$c[1]}";
                        } else {
                            throw new \InvalidArgumentException();
                        }
                    }, $this->update)
                );
        }

        $query = "{$action} {$ignore} INTO {$this->table->name} ({$columns}) VALUES {$values} {$update}";
        $this->statement = $this->db->prepare($query);
        $this->preparedForCount = $this->currentStacked;
    }

    /**
     * @return static
     */
    final public function stack(IInsertable $obj): self {
        foreach($this->columns as $column) {
            $this->data[] = $obj->getDbValue($column);
        }
        $this->currentStacked++;

        if ($this->stackSize > 0 && $this->currentStacked >= $this->stackSize) {
            $this->persist();
        }
        return $this;
    }

    /**
     * @return static
     */
    final public function persist(?IInsertable $obj=null): self {
        if (count($this->data) == 0 && is_null($obj)) {
            return $this;
        }

        if (!is_null($obj)) {
            $this->stack($obj);
        }

        $this->prepare();
        $this->execute($this->data);
        $this->insertedRowCount += $this->statement->rowCount();
        $this->clear();
        return $this;
    }

    public function clear(): void {
        $this->data = [];
        $this->currentStacked = 0;
    }

    public function reset(): void {
        $this->clear();
        $this->insertedRowCount = 0;
    }

    public function dump(): array {
        $this->prepare();
        return [
            $this->statement->queryString,
            $this->data
        ];
    }

    /**
     * @throws NotSupportedException
     */
    public function getInsertedId(): int {
        $id = $this->db->lastInsertId();
        if ($id === false) {
            return 0;
        } elseif (is_numeric($id)) {
            return (int)$id;
        } else {
            // right now we only support mysql databases, which return numeric IDs
            throw new NotSupportedException();
        }
    }

    public function getInsertedRowCount(): int {
        return $this->insertedRowCount;
    }
}
