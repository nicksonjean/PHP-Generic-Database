<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Junction: string
{
    use EnumBc;

    case NONE = 'NONE';
    case CONJUNCTION = 'CONJUNCTION';
    case DISJUNCTION = 'DISJUNCTION';
}
