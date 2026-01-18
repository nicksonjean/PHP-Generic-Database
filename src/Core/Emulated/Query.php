<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedIntEnum;

final class Query extends EmulatedIntEnum
{
    protected const RAW = 0;
    protected const PREPARED = 1;
}

