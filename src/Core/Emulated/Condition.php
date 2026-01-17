<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Condition extends EmulatedStringEnum
{
    protected const NONE = 'NONE';
    protected const CONJUNCTION = 'CONJUNCTION';
    protected const DISJUNCTION = 'DISJUNCTION';
}
