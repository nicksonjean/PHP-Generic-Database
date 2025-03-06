<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Insert: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case IGNORE = 'IGNORE';
}
