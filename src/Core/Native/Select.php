<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Select: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case DISTINCT = 'DISTINCT';
}
