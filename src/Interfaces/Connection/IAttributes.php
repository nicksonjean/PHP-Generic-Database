<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines the attributes for a database connection.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IAttributes
 */
interface IAttributes
{
    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @return void
     */
    public function define(): void;
}
