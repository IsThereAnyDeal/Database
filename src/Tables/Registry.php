<?php
namespace IsThereAnyDeal\Database\Tables;

class Registry
{
    private static Registry $instance;

    /** @var array<class-string<Table>, list<Table>> */
    private static array $registry = [];

    /** @var array<class-string<Table>, int> */
    private static array $counter = [];

    static function context(): self {
        self::$counter = [];
        return self::$instance ??= new self();
    }

    private readonly Context $context;

    private function __construct() {
        $this->context = new Context();
    }

    /**
     * @template T of Table
     * @param class-string<T> $className
     * @return T
     */
    public function get(string $className): Table {
        $i = self::$counter[$className] = (self::$counter[$className] ?? -1) + 1;
        // @phpstan-ignore-next-line I don't know how to type this
        return self::$registry[$className][$i] ??= new $className($this->context);
    }
}