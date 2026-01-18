<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Column: string
{
    use EnumBc;

    case METADATA = 'METADATA';
    case FUNCTION = 'FUNCTION';
}
