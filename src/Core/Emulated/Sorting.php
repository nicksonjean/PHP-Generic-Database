<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Sorting extends EmulatedStringEnum
{
    protected const NONE = 'NONE';
    protected const ASCENDING = 'ASCENDING';
    protected const DESCENDING = 'DESCENDING';
    protected const METADATA = 'METADATA';
    protected const FUNCTION = 'FUNCTION';
}
