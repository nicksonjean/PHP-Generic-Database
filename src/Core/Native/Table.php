<?php

namespace GenericDatabase\Core\Native;

use Mabe\Enum\Cl\EnumBc;

enum Table: string
{
    use EnumBc;

    case METADATA = 'METADATA';
}
