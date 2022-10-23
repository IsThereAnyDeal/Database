<?php
namespace IsThereAnyDeal\Database\Tests\_testObjects\DTO;

abstract class ConstructorBaseDTO
{
    public int $id;
    public string $constructorValue = "Constructor was not called";

    public function __construct() {

        if (isset($this->id)) {
            $this->constructorValue = "Constructor called, ID: {$this->id}";
        } else {
            $this->constructorValue = "Constructor called before setting ID";
        }
    }
}
