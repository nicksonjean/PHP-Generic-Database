<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Join extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const SELF = 'SELF';
    protected const LEFT = 'LEFT';
    protected const RIGHT = 'RIGHT';
    protected const INNER = 'INNER';
    protected const OUTER = 'OUTER';
    protected const CROSS = 'CROSS';
}

