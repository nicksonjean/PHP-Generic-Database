<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Union: string
{
    use EnumBc;

    case DISTINCT = 'DISTINCT';
    case INDISTINCT = 'INDISTINCT';
    case SUBQUERY = 'SUBQUERY';
    case RAW = 'RAW';
}
