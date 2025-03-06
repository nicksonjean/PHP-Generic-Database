<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Limit: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case OFFSET = 'OFFSET';
}
