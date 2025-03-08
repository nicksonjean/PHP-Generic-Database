<?php

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Helpers\Exceptions;

/**
 * This interface defines the structure for a Data Source Name (DSN) in the application.
 * Implementing classes should provide the necessary methods to handle DSN configurations.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IDSN
 */
interface IDSN
{
    /**
     * Parses the DSN (Data Source Name) and returns it as a string.
     *
     * @return string|Exceptions The parsed DSN string or an exception if parsing fails.
     */
    public function parse(): string|Exceptions;
}
