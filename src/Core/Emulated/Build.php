<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedIntEnum;

final class Build extends EmulatedIntEnum
{
    protected const BEFORE = 0;
    protected const AFTER = 1;
}
