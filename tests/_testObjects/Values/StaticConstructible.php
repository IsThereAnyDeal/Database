<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Values;

class StaticConstructible
{
    public static function get(string $value): self {
        $result = new StaticConstructible();
        $result->value = $value;
        return $result;
    }

    private string $value;

    private function __construct() {}

    public function getValue(): string {
        return $this->value;
    }
}
