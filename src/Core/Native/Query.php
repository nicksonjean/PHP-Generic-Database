<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Query: int
{
    use EnumBc;

    case RAW = 0;
    case PREPARED = 1;
}
