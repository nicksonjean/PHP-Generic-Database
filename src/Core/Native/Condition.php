<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Condition: string
{
    use EnumBc;

    case NONE = 'NONE';
    case CONJUNCTION = 'CONJUNCTION';
    case DISJUNCTION = 'DISJUNCTION';
}
