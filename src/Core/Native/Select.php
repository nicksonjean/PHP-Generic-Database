<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Select: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case DISTINCT = 'DISTINCT';
}
