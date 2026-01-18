<?php

namespace GenericDatabase\Interfaces\Connection;

use ReflectionException;
use GenericDatabase\Interfaces\IConnection;

/**
 * This interface defines the contract for handling arguments in database connections.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IArguments
 */
interface IArguments
{
    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null;

    /**
     * Triggered when invoking inaccessible methods in a static context
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     * @throws ReflectionException
     */
    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null;
}

