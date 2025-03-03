<?php

namespace GenericDatabase\Engine\PDO\Connection;

use PDO;

class XPDO extends PDO
{
    /**
     * Connection attribute to set the default report mode.
     */
    final public const ATTR_REPORT = 1110;

    /**
     * Turns reporting off
     */
    final public const REPORT_OFF = 0;

    /**
     * Report errors from mysqli function calls
     */
    final public const REPORT_ERROR = 1;

    /**
     * Throw exception for errors instead of warnings
     */
    final public const REPORT_STRICT = 2;

    /**
     * Report if no index or bad index was used in a query
     */
    final public const REPORT_INDEX = 4;

    /**
     * Report all errors
     */
    final public const REPORT_ALL = 255;
}
