<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;

#[Construction(EConstructionType::AfterFetch)]
class ConstructorPostFetchDTO extends ConstructorBaseDTO
{}
