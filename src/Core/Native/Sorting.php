<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Sorting: string
{
    use EnumBc;

    case NONE = 'NONE';
    case ASCENDING = 'ASCENDING';
    case DESCENDING = 'DESCENDING';
    case METADATA = 'METADATA';
    case FUNCTION = 'FUNCTION';
}
