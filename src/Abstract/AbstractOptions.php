<?php

namespace GenericDatabase\Abstract;

use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptionsAbstract;

/**
 *
 * The `GenericDatabase\Abstract\AbstractOptions` class is an abstract base class implements the `IOptionsAbstract` interface and that provides a common interface for managing database connection options.
 * It allows dynamic setting and getting of options using a connection instance and an options array, and provides a base implementation for all options classes.
 *
 * Main functionalities:
 * - Provides a base class for managing database connection options.
 * - Allows dynamic setting and getting of options using a connection instance and an options array.
 * - Provides a common interface for all options classes.
 *
 * Methods:
 * - `getInstance(): IConnection:` Returns the static instance of IConnection.
 * - `set(string $name, mixed $value): void:` Sets an option value dynamically by calling a method on the connection instance.
 * - `get(string $name): mixed:` Retrieves an option value dynamically by calling a method on the connection instance.
 * - `getOptions(?int $type = null)`: mixed:` Returns all options or a specific type of options if the `$type` parameter is provided.
 *
 * Fields:
 * - `$instance`: The connection instance used for dynamic operations.
 * - `$options`: The options handler for managing configuration.
 *
 * Note that this class provides a structure for managing dynamic properties using a connection instance and options array. It allows setting and getting options dynamically by utilizing the Run::call method to invoke methods on the connection instance.
 * Note that this class uses a static array `$options` to store connection options, and a static property `$instance` to store the database connection instance. The `Run::call` method is used to dynamically call methods on the connection instance.
 *
 * @package PHP-Generic-Database
 * @subpackage Abstract
 * @category Database
 * @abstract
 */
abstract class AbstractOptions implements IOptionsAbstract
{
    /**
     * Array to store connection options
     *
     * @var array
     */
    protected static array $options = [];

    /**
     * Database connection instance
     *
     * @var IConnection
     */
    protected static IConnection $instance;

    /**
     * Initialize options with a connection instance
     *
     * @param IConnection $instance The database connection instance
     */
    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
    }

    /**
     * Get the database connection instance
     *
     * @return IConnection The current database connection instance
     */
    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Set an option value using dynamic method call
     *
     * @param string $name The name of the option to set
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    /**
     * Get an option value using dynamic method call
     *
     * @param string $name The name of the option to get
     * @return mixed The value of the requested option
     */
    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    /**
     * Get all options or a specific option type defined by user
     *
     * @param int|null $type Optional type of options to retrieve
     * @return mixed All options or specific type options if type parameter provided
     */
    public function getOptions(?int $type = null): mixed
    {
        return !is_null($type) ? self::$options[$type] ?? null : self::$options;
    }
}

