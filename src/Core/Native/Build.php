<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Build: int
{
    use EnumBc;

    case BEFORE = 0;
    case AFTER = 1;
}
