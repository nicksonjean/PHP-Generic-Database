<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Grouping: string
{
    use EnumBc;

    case DEFAULT = 'DEFAULT';
    case COMPOUND = 'COMPOUND';
    case METADATA = 'METADATA';
    case FUNCTION = 'FUNCTION';
}
