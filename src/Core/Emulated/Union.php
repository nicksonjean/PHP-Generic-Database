<?php

namespace GenericDatabase\Core\Emulated;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Union extends EmulatedStringEnum
{
    protected const DISTINCT = 'DISTINCT';
    protected const INDISTINCT = 'INDISTINCT';
    protected const SUBQUERY = 'SUBQUERY';
    protected const RAW = 'RAW';
}
