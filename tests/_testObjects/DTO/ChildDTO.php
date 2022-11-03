<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;

#[Construction(EConstructionType::AfterFetch)]
class ChildDTO extends ParentDTO
{
    private int $timestamp;

    public function __construct(int $id) {
        $this->id = $id;
    }

    public function getTimestamp(): int {
        return $this->timestamp;
    }
}
