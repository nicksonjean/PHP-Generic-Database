<?php

namespace GenericDatabase\Core;

use Mabe\Enum\Cl\EmulatedStringEnum;

final class Having extends EmulatedStringEnum
{
    protected const DEFAULT = 'DEFAULT';
    protected const NONE = 'NONE';
    protected const FUNCTION = 'FUNCTION';
    protected const NEGATION = 'NEGATION';
    protected const AFFIRMATION = 'AFFIRMATION';
    protected const BETWEEN = 'BETWEEN';
    protected const IN = 'IN';
    protected const LIKE = 'LIKE';
    protected const EXISTS = 'EXISTS';
    protected const ISNULL = 'ISNULL';
    protected const IFNULL = 'IFNULL';
}
