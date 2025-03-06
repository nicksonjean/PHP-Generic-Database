<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Select extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const DISTINCT = 'DISTINCT';
}
