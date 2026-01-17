<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Column extends EmulatedStringEnum
{
    protected const METADATA = 'METADATA';
    protected const FUNCTION = 'FUNCTION';
}
