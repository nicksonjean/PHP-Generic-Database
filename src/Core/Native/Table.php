<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EnumBc;

enum Table: string
{
    use EnumBc;

    case METADATA = 'METADATA';
}
