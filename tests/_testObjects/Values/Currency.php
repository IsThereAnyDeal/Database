<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\Values;

class Currency implements \Stringable
{
    public function __construct(
        public readonly string $code
    ) {}

    public function __toString(): string {
        return $this->code;
    }
}
