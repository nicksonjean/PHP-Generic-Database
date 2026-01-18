<?php

namespace GenericDatabase\Abstract;

use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IAttributesAbstract;

/**
 * The `GenericDatabase\Abstract\AbstractAttributes` class implements the `IAttributesAbstract` interface and serves as a base class for
 * defining attributes in the PHP-Generic-Database project. It provides a common interface and shared functionality for all attribute classes.
 * This class provides a structure for managing dynamic properties using a connection instance and options handler. It allows setting
 * and getting properties dynamically by utilizing the Run::call method to invoke methods on the connection instance.
 *
 * Main functionalities:
 * - Manages the connection instance and options handler.
 * - Provides a base implementation for defining attributes in the PHP-Generic-Database project.
 * - Allows dynamic setting and getting of attribute values.
 * - Provides a common interface for all attribute classes.
 *
 * Methods:
 * - `getInstance(): IConnection:` Returns the static instance of IConnection.
 * - `getOptionsHandler(): IOptions:` Returns the static instance of IOptions.
 * - `set(string $name, mixed $value): void:` Sets a property dynamically on the connection instance.
 * - `get(string $name): mixed:` Retrieves a property dynamically from the connection instance.
 *
 * Fields:
 * - `$instance`: The connection instance used for dynamic operations.
 * - `$optionsHandler`: The options handler for managing configuration.
 *
 * @package PHP-Generic-Database
 * @subpackage Abstract
 * @category Database
 * @abstract
 */
abstract class AbstractAttributes implements IAttributesAbstract
{
    /**
     * @var IConnection Instance of the connection interface
     */
    protected static IConnection $instance;

    /**
     * @var IOptions Instance of the options handler interface
     */
    protected static IOptions $optionsHandler;

    /**
     * Constructor for AbstractAttributes.
     *
     * @param IConnection $instance The connection instance to use
     * @param IOptions $optionsHandler The options handler instance to use
     */
    public function __construct(IConnection $instance, IOptions $optionsHandler)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
    }

    /**
     * Get the connection instance.
     *
     * @return IConnection The current connection instance
     */
    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Get the options handler instance.
     *
     * @return IOptions The current options handler instance
     */
    public function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    /**
     * Set an attribute value using dynamic method call.
     *
     * @param string $name The name of the attribute to set
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    /**
     * Get an attribute value using dynamic method call.
     *
     * @param string $name The name of the attribute to get
     * @return mixed The value of the requested attribute
     */
    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }
}

