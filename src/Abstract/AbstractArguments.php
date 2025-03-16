<?php

namespace GenericDatabase\Abstract;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Helpers\Parsers\INI;
use GenericDatabase\Helpers\Parsers\XML;
use GenericDatabase\Helpers\Parsers\JSON;
use GenericDatabase\Helpers\Parsers\YAML;
use GenericDatabase\Helpers\Parsers\NEON;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IArgumentsAbstract;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;

/**
 * The `GenericDatabase\Abstract\AbstractArguments` class is an abstract  class implements the `IArgumentsAbstract` interface and that provides a framework for handling database connection arguments.
 * It manages static instances of IConnection, IOptions, and IArgumentsStrategy and provides methods to manipulate and retrieve these instances and manages static instances of IConnection, IOptions,
 * and IArgumentsStrategy, and provides methods to manipulate and retrieve these instances. It includes functionality for setting types, transforming variables into constants, and handling arguments
 * in various formats such as JSON, XML, INI, and YAML. The class also supports dynamic method invocation and acts as a factory for instantiating classes with specific arguments.
 *
 * Main functionalities:
 * - Manages static instances of IConnection, IOptions, and IArgumentsStrategy.
 * - Provides methods for setting types, transforming variables into constants, and handling arguments in various formats.
 * - Supports dynamic method invocation and acts as a factory for instantiating classes with specific arguments.
 * - Offers a flexible and extensible framework for handling database connection arguments.
 *
 * Methods:
 * - `getInstance(): IConnection:` Returns the static instance of IConnection.
 * - `getOptionsHandler(): IOptions:` Returns the static instance of IOptions.
 * - `getArgumentsStrategy(): IArgumentsStrategy:` Returns the static instance of IArgumentsStrategy.
 * - `setType(mixed $value): string|int|bool:` Determines the type of value and returns it as a string, int, or bool.
 * - `setConstant(array $value): array:` Transforms variables into constants.
 * - `callArgumentsByFormat(string $format, mixed $arguments):` IConnection: Determines the type of arguments based on the format (json, xml, ini, or yaml) and sets the corresponding values in the IConnection instance.
 * - `callWithByStaticArray(array $arguments): IConnection:` Sets values in the IConnection instance using a static array format.
 * - `callWithByStaticArgs(array $arguments): IConnection:` Same as callWithByStaticArray, but with a different name.
 * - `call(string $name, array $arguments): IConnection|string|int|bool|array|null:` Triggers when invoking inaccessible methods in an object context. It sets or gets values in the IConnection instance based on the method name.
 * - `callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null:` Works like a factory, identifying the way the class is instantiated and its arguments. It calls the corresponding method based on the name and arguments.
 *
 * Fields:
 * - `$instance`: The connection instance used for dynamic operations.
 * - `$optionsHandler`: The options handler for managing configuration.
 * - `$argumentsStrategy`: The arguments strategy handler for managing configuration.
 *
 * @package PHP-Generic-Database
 * @subpackage Abstract
 * @category Database
 * @abstract
 */
abstract class AbstractArguments implements IArgumentsAbstract
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
     * @var IArgumentsStrategy Instance of the arguments strategy interface
     */
    protected static IArgumentsStrategy $argumentsStrategy;

    /**
     * Constructor for AbstractArguments.
     *
     * @param IConnection $instance The connection instance to use
     * @param IOptions $optionsHandler The options handler instance to use
     * @param IArgumentsStrategy $argumentsStrategy The arguments strategy instance to use
     */
    public function __construct(IConnection $instance, IOptions $optionsHandler, IArgumentsStrategy $argumentsStrategy)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
        self::$argumentsStrategy = $argumentsStrategy;
    }

    /**
     * Get the connection instance.
     *
     * @return IConnection The current connection instance
     */
    public static function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Get the options handler instance.
     *
     * @return IOptions The current options handler instance
     */
    public static function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    /**
     * Get the arguments strategy instance.
     *
     * @return IArgumentsStrategy The current arguments strategy instance
     */
    public static function getArgumentsStrategy(): IArgumentsStrategy
    {
        return self::$argumentsStrategy;
    }

    /**
     * Determines the type that will receive treatment
     *
     * @param mixed $value Value to determine type for
     * @return string|int|bool The determined type
     */
    public static function setType(mixed $value): string|int|bool
    {
        return Generators::setType($value);
    }

    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     */
    public static function setConstant(array $value): array
    {
        return self::getArgumentsStrategy()->setConstant($value);
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments Arguments to parse
     * @return IConnection The connection instance
     */
    public static function callArgumentsByFormat(string $format, mixed $arguments): IConnection
    {
        $data = match ($format) {
            'json' => JSON::parseJSON(...$arguments),
            'ini' => INI::parseINI(...$arguments),
            'xml' => XML::parseXML(...$arguments),
            'yaml' => YAML::parseYAML(...$arguments),
            'neon' => NEON::parseNEON(...$arguments),
            default => null,
        };
        if ($data) {
            foreach ($data as $key => $value) {
                if (mb_strtolower($key) === 'options') {
                    self::getInstance()->$key = self::$argumentsStrategy->setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value]);
                } else {
                    self::getInstance()->$key = self::setType($value);
                }
            }
        }
        return self::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments Array of arguments
     * @return IConnection The connection instance
     */
    private static function callWithByStaticArray(array $arguments): IConnection
    {
        $instance = self::getInstance();
        foreach (array_keys($arguments) as $property) {
            $instance->$property = $arguments[$property] ?? null;
        }
        return $instance;
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments Array of arguments
     * @return IConnection The connection instance
     * @noinspection PhpUnused
     */
    public static function callWithByStaticArgs(array $arguments): IConnection
    {
        return self::callWithByStaticArray($arguments);
    }

    /**
     * Triggered when invoking inaccessible methods in an object context
     *
     * @param string $name Name of the method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null The result of the method call
     */
    public function call(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        $method = substr($name, 0, 3);
        $field = lcfirst(substr($name, 3));
        if ($method === 'set') {
            $this->getInstance()->$field = $arguments[0] ?? null;
        } elseif ($method === 'get') {
            return $this->getInstance()->$field ?? null;
        }
        return $this->getInstance();
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name Name of the static method
     * @param array $arguments Array of arguments
     * @return IConnection|string|int|bool|array|null The result of the static call
     */
    public static function callStatic(string $name, array $arguments): IConnection|string|int|bool|array|null
    {
        $argumentsFile = Arrays::assocToIndex(Arrays::recombine($arguments));
        return match ($name) {
            'new' => match (true) {
                JSON::isValidJSON(...$argumentsFile) => self::callArgumentsByFormat('json', $argumentsFile),
                YAML::isValidYAML(...$argumentsFile) => self::callArgumentsByFormat('yaml', $argumentsFile),
                INI::isValidINI(...$argumentsFile) => self::callArgumentsByFormat('ini', $argumentsFile),
                XML::isValidXML(...$argumentsFile) => self::callArgumentsByFormat('xml', $argumentsFile),
                NEON::isValidNEON(...$argumentsFile) => self::callArgumentsByFormat('neon', $argumentsFile),
                default => Arrays::isAssoc(...$argumentsFile)
                    ? self::callWithByStaticArray(...$arguments)
                    : self::callWithByStaticArgs($arguments)
            },
            default => call_user_func_array([self::getInstance(), $name], $arguments),
        };
    }
}
