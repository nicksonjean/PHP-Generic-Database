<?php

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Interfaces\IConnection;

/**
 * This interface defines the abstract options for database connections.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IOptionsAbstract
 */
interface IOptionsAbstract
{
    /**
     * Get the database connection instance
     *
     * @return IConnection The current database connection instance
     */
    public function getInstance(): IConnection;

    /**
     * Set an option value using dynamic method call
     *
     * @param string $name The name of the option to set
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $name, mixed $value): void;

    /**
     * Get an option value using dynamic method call
     *
     * @param string $name The name of the option to get
     * @return mixed The value of the requested option
     */
    public function get(string $name): mixed;

    /**
     * Get all options or a specific option type defined by user
     *
     * @param int|null $type Optional type of options to retrieve
     * @return mixed All options or specific type options if type parameter provided
     */
    public function getOptions(?int $type = null): mixed;
}

