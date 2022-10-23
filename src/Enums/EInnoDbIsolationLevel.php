<?php
namespace IsThereAnyDeal\Database\Enums;

enum EInnoDbIsolationLevel: string
{
    case READ_UNCOMMITTED = "READ UNCOMMITTED";
    case READ_COMMITTED = "READ COMMITTED";
    case REPEATABLE_READ = "REPEATABLE READ"; // default
    case SERIALIZABLE = "SERIALIZABLE";
}
