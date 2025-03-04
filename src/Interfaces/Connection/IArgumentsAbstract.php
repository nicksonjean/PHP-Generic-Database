<?php

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;

/**
 * Defines an abstract interface for managing connection arguments and options.
 *
 * This interface provides methods for retrieving the connection instance, options handler, and arguments strategy.
 * It also defines methods for setting the argument type, calling arguments by format, and calling with static arguments.
 * Additionally, it includes methods for calling with a specific name and arguments, as well as calling statically with a name and arguments.
 */
interface IArgumentsAbstract
{
    /**
     * Gets the connection instance.
     * @return IConnection The connection instance.
     */
    public static function getInstance(): IConnection;

    /**
     * Gets the options handler.
     * @return IOptions The options handler.
     */
    public static function getOptionsHandler(): IOptions;

    /**
     * Gets the arguments strategy.
     * @return IArgumentsStrategy The arguments strategy.
     */
    public static function getArgumentsStrategy(): IArgumentsStrategy;

    /**
     * Sets the argument type.
     * @param mixed $value The value to set the argument type to.
     * @return string|int|bool The set argument type.
     */
    public static function setType(mixed $value): string|int|bool;

    /**
     * Calls the arguments by format.
     * @param string $format The format to call the arguments by.
     * @param mixed $arguments The arguments to call.
     * @return IConnection The connection instance.
     */
    public static function callArgumentsByFormat(string $format, mixed $arguments): IConnection;

    /**
     * Calls with static arguments.
     * @param array $arguments The arguments to call with.
     * @return IConnection The connection instance.
     */
    public static function callWithByStaticArgs(array $arguments): IConnection;

    /**
     * Calls with a specific name and arguments.
     * @param string $name The name to call with.
     * @param array $arguments The arguments to call with.
     * @return IConnection|string|int|bool|array|null The result of the call.
     */
    public function call(string $name, array $arguments): IConnection|string|int|bool|array|null;

    /**
     * Calls statically with a name and arguments.
     * @param string $name The name to call statically with.
     * @param array $arguments The arguments to call statically with.
     * @return IConnection|string|int|bool|array|null The result of the static call.
     */
    public static function callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null;
}
