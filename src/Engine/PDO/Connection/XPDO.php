<?php

namespace GenericDatabase\Engine\PDO\Connection;

use PDO;

class XPDO extends PDO
{
    /**
     * Connection attribute to set the default report mode.
     */
    public const ATTR_REPORT = 1110;

    /**
     * Turns reporting off
     */
    public const REPORT_OFF = 0;

    /**
     * Report errors from mysqli function calls
     */
    public const REPORT_ERROR = 1;

    /**
     * Throw exception for errors instead of warnings
     */
    public const REPORT_STRICT = 2;

    /**
     * Report if no index or bad index was used in a query
     */
    public const REPORT_INDEX = 4;

    /**
     * Report all errors
     */
    public const REPORT_ALL = 255;
}

