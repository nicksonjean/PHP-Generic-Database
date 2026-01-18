<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Grouping extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const COMPOUND = 'COMPOUND';
    protected const METADATA = 'METADATA';
    protected const FUNCTION = 'FUNCTION';
}
