<?php

namespace IsThereAnyDeal\Database\Enums;

enum EConstructionType
{
    case None;
    case BeforeFetch;
    case AfterFetch;
}
