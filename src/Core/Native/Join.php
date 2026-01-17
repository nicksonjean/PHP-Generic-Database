<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Join: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case SELF = 'SELF';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case INNER = 'INNER';
    case OUTER = 'OUTER';
    case CROSS = 'CROSS';
}
