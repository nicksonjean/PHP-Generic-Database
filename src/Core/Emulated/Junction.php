<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Junction extends EmulatedStringEnum
{
    protected const NONE = 'NONE';
    protected const CONJUNCTION = 'CONJUNCTION';
    protected const DISJUNCTION = 'DISJUNCTION';
}

