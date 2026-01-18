<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Limit extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const OFFSET = 'OFFSET';
}

