<?php
namespace IsThereAnyDeal\Database\Sql\Create;

use BackedEnum;
use Ds\Set;
use Ds\Vector;
use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\DbDriver;
use IsThereAnyDeal\Database\Exceptions\InvalidParamTypeException;
use IsThereAnyDeal\Database\Exceptions\SqlException;
use IsThereAnyDeal\Database\Sql\ParamParser;
use IsThereAnyDeal\Database\Sql\SqlQuery;
use IsThereAnyDeal\Database\Tables\Column;
use IsThereAnyDeal\Database\Tables\Table;

/**
 * @template T of object
 */
class SqlInsertQuery extends SqlQuery {

    private Table $table;
    private bool $ignore = false;
    protected bool $replace = false;

    /** @var Set<string> */
    private Set $columns;

    private ?string $rowAlias = null;

    /** @var list<string> */
    private array $columnAliases = [];

    private ?string $selectQuery = null;

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

    /** @var Vector<null|scalar> */
    private Vector $values;

    /** @var ?\Closure(T): list<null|scalar> $valueMapper */
    private mixed $valueMapper = null;

    private bool $clearInserted = false;
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

    /**
     * @param list<string> $columnAliases
     */
    final public function rowAlias(string $rowAlias, array $columnAliases=[]): static {
        $this->rowAlias = $rowAlias;
        $this->columnAliases = $columnAliases;
        return $this;
    }

    final public function ignore(bool $value=true): static {
        $this->ignore = $value;
        return $this;
    }

    /**
     * @param non-empty-string $query
     */
    final public function select(string $query): static {
        $this->selectQuery = $query;
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

    /**
     * @param T $obj
     * @return static
     * @throws \ReflectionException
     */
    final public function stack(object $obj): static {
        if (!is_null($this->selectQuery)) {
            throw new \LogicException("Can't store object with INSERT SELECT query");
        }

        if ($this->clearInserted) {
            $this->insertedId = 0;
            $this->insertedRowCount = 0;
            $this->clearInserted = false;
        }

        if (is_null($this->valueMapper)) {
            $this->valueMapper = ValueMapper::getObjectValueMapper($this->columns, $obj);
        }

        /** @var list<scalar> $scalars */
        $scalars = call_user_func($this->valueMapper, $obj);
        $this->values->push(...$scalars);
        $this->currentStacked++;

        if ($this->stackSize > 0 && $this->currentStacked >= $this->stackSize) {
            $this->executePersist();
        }
        return $this;
    }

    /**
     * @param array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $params
     */
    private function buildQuery(array $params=[]): string {
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

        if (is_null($this->selectQuery)) {
            $valueListTemplate = ValueMapper::getValueTemplate(count($this->columns));
            $values = $valueListTemplate.str_repeat(",\n{$valueListTemplate}", $this->currentStacked-1);
            $valuesSql = "VALUES {$values}";
        } else {
            $parser = new ParamParser(preg_replace("#^SELECT\s+#i", "", $this->selectQuery), $params); // @phpstan-ignore-line
            $valuesSql = "SELECT ".$parser->getQuery();
            $this->values->clear();
            $this->values->push(...$parser->getValues());
        }

        $alias = "";
        if (!is_null($this->rowAlias)) {
            $alias = " AS {$this->rowAlias} ";
            if (!empty($this->columnAliases)) {
                if (count($this->columnAliases) != count($this->columns)) {
                    throw new \InvalidArgumentException("Column count and column alias count doesn't match");
                }

                $alias .= "(".implode(",", $this->columnAliases).") ";
            }
        }

        return "{$action}{$ignore} INTO `{$this->table->__name__}` ({$columns})\n{$valuesSql}{$alias}{$update}";
    }

    /**
     * @param null|T|array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $objOrParams
     * @throws InvalidParamTypeException
     * @throws SqlException
     * @throws \ReflectionException
     */
    private function executePersist(null|object|array $objOrParams=null): void {
        if (is_null($this->selectQuery) && count($this->values) == 0 && is_null($objOrParams)) {
            return;
        }

        $params = [];
        if (!is_null($objOrParams)) {
            if (is_null($this->selectQuery)) {
                if (!is_object($objOrParams)) {
                    throw new \InvalidArgumentException();
                }

                $this->stack($objOrParams);
            } else {
                if (!is_array($objOrParams)) {
                    throw new \InvalidArgumentException();
                }

                $params = $objOrParams;
            }
        }

        try {
            $statement = $this->prepare($this->buildQuery($params), $this->values->toArray());
            $this->execute($statement);
            $this->insertedId = $this->getLastInsertedId();
            $this->insertedRowCount += $statement->rowCount();
        } catch (\PDOException $e) {
            $this->clear();
            throw $e;
        }
        $this->clear();
    }

    /**
     * @param null|T|array<string, null|scalar|BackedEnum|list<null|scalar|BackedEnum>> $objOrParams
     * @return static
     * @throws InvalidParamTypeException
     * @throws SqlException
     * @throws \ReflectionException
     */
    final public function persist(null|object|array $objOrParams=null): static {
        $this->executePersist($objOrParams);
        $this->clearInserted = true;
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
