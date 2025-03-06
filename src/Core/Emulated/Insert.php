<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Insert extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const IGNORE = 'IGNORE';
}
