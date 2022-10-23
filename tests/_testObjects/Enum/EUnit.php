<?php

namespace IsThereAnyDeal\Database\Tests\_testObjects\Enum;

enum EUnit
{
    case FirstValue;
    case SecondValue;

    public function serialize(): string {
        return match ($this) {
            self::FirstValue => "first",
            self::SecondValue => "second"
        };
    }

    /** @return array{int, string} */
    public function serializePair(): array {
        return match ($this) {
            self::FirstValue => [1, "first"],
            self::SecondValue => [2, "second"]
        };
    }

    public static function staticSerialize(EUnit $value): string {
        return match ($value) {
            self::FirstValue => "first",
            self::SecondValue => "second"
        };
    }

    /** @return array{int, string} */
    public static function staticSerializePair(EUnit $value): array {
        return match ($value) {
            self::FirstValue => [1, "first"],
            self::SecondValue => [2, "second"]
        };
    }
}
