<?php
namespace IsThereAnyDeal\Database\Tables;

class Context
{
    private int $counter = 1;

    public function __construct(
        private readonly string $alias
    ) {
        if (empty($this->alias)) {
            throw new \ErrorException("Table alias may not be empty");
        }
    }

    public function getAlias(): string {
        /**
         * Note the _ suffix, which is meant to prevent conflicts with
         * more complicated queries where we use custom, simple alias (commonly 'i', 'o', 't')
         */
        $num = $this->counter++;
        return ($this->alias).($num > 1 ? $num : "")."_";
    }
}
