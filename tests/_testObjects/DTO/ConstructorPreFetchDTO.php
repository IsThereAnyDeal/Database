<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

use IsThereAnyDeal\Database\Attributes\Construction;
use IsThereAnyDeal\Database\Enums\EConstructionType;

#[Construction(EConstructionType::BeforeFetch)]
class ConstructorPreFetchDTO extends ConstructorBaseDTO
{}
