<?php

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;

interface IAttributesAbstract
{
    /**
     * Get the connection instance.
     *
     * @return IConnection The current connection instance
     */
    public function getInstance(): IConnection;

    /**
     * Get the options handler instance.
     *
     * @return IOptions The current options handler instance
     */
    public function getOptionsHandler(): IOptions;

    /**
     * Set an attribute value using dynamic method call.
     *
     * @param string $name The name of the attribute to set
     * @param mixed $value The value to set
     * @return void
     */
    public function set(string $name, mixed $value): void;

    /**
     * Get an attribute value using dynamic method call.
     *
     * @param string $name The name of the attribute to get
     * @return mixed The value of the requested attribute
     */
    public function get(string $name): mixed;
}
