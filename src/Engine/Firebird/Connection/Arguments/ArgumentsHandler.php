<?php

namespace GenericDatabase\Engine\Firebird\Connection\Arguments;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Abstract\AbstractArguments;
use GenericDatabase\Interfaces\Connection\IArguments;

class ArgumentsHandler extends AbstractArguments implements IArguments
{
    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null
     */
    public function __call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return $this->call($name, $arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return IConnection|string|int|bool|array|null
     */
    public static function __callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        return self::callStatic($name, $arguments);
    }
}

