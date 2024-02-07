<?php
namespace IsThereAnyDeal\Database;

final class Credentials
{
    public function __construct(
        public readonly string $user,
        public readonly string $password
    ) {}
}
