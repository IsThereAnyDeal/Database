<?php
namespace IsThereAnyDeal\Database\Tables;

use Ds\Map;

class Registry
{
    private static Registry $instance;

    static function context(): self {
        $instance = self::$instance ??= new self();
        $instance->counter = [];
        return $instance;
    }

    /** @var array<class-string<Table>, int> */
    private array $counter = [];

    /** @var array<class-string<Table>, list<Table>> */
    private array $registry = [];

    /** @var Map<string, Context>  Map of <alias, Context> */
    private Map $contexts;

    /** @var array<string, string>  Map of <tableName, alias> */
    private array $aliases = [];

    private function __construct() {
        $this->contexts = new Map();
    }

    private function getTableAlias(string $className): string {
        if (!isset($this->aliases[$className])) {
            $alias = (string)preg_replace("#.+\\\#", "", $className)
                |> (fn(string $value) => (string)preg_replace("#^T([A-Z])#", "$1", $value))
                |> (fn(string $value) => (string)preg_replace("#[^A-Z]#", "", $value))
                |> strtolower(...);

            if (empty($alias)) {
                $alias = "t";
            }

            $this->aliases[$className] = $alias;
        }
        return $this->aliases[$className];
    }

    /**
     * @template T of Table
     * @param class-string<T> $className
     * @return T
     */
    private function makeTable(string $className): Table {
        $alias = $this->getTableAlias($className);
        $context = $this->contexts->get($alias, null);
        if (is_null($context)) {
            $context = new Context($alias);
            $this->contexts->put($alias, $context);
        }
        return new $className($context);
    }

    /**
     * @template T of Table
     * @param class-string<T> $className
     * @return T
     */
    public function get(string $className): Table {
        $i = $this->counter[$className] = ($this->counter[$className] ?? -1) + 1;
        // @phpstan-ignore-next-line I don't know how to type this
        return $this->registry[$className][$i] ??= $this->makeTable($className);
    }
}