<?php

namespace GenericDatabase\Abstract;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\JSON;
use GenericDatabase\Helpers\Parsers\INI;
use GenericDatabase\Helpers\Parsers\YAML;
use GenericDatabase\Helpers\Parsers\XML;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IArgumentsStrategy;
use ReflectionException;

abstract class AbstractArguments
{
    protected static IConnection $instance;

    protected static IOptions $optionsHandler;

    protected static IArgumentsStrategy $argumentsStrategy;

    public function __construct(IConnection $instance, IOptions $optionsHandler, IArgumentsStrategy $argumentsStrategy)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
        self::$argumentsStrategy = $argumentsStrategy;
    }

    public static function getInstance(): IConnection
    {
        return self::$instance;
    }

    public static function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    public static function getArgumentsStrategy(): IArgumentsStrategy
    {
        return self::$argumentsStrategy;
    }

    /**
     * Determines the type that will receive treatment
     *
     * @param mixed $value
     * @return string|int|bool
     */
    private static function setType(mixed $value): string|int|bool
    {
        return Generators::setType($value);
    }

    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     * @throws ReflectionException
     */
    public static function setConstant(array $value): array
    {
        return self::$argumentsStrategy->setConstant($value);
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return IConnection
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public static function callArgumentsByFormat(string $format, mixed $arguments): IConnection
    {
        $data = match ($format) {
            'json' => JSON::parseJSON(...$arguments),
            'ini' => INI::parseINI(...$arguments),
            'xml' => XML::parseXML(...$arguments),
            'yaml' => YAML::parseYAML(...$arguments),
            default => null,
        };
        if ($data) {
            foreach ($data as $key => $value) {
                if (mb_strtolower($key) === 'options') {
                    self::getInstance()->$key = self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value]) ?? null;
                } else {
                    self::getInstance()->$key = self::setType($value) ?? null;
                }
            }
        }
        return self::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return IConnection
     */
    private static function callWithByStaticArray(array $arguments): IConnection
    {
        foreach ($arguments as $key => $value) {
            self::getInstance()->$key = $value ?? null;
        }
        return self::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return IConnection
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
     * @return IConnection|string|int|bool|array|null
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
     * @param string $name
     * @param array $arguments
     * @return IConnection
     * @throws ReflectionException
     */
    public static function callStatic(string $name, array $arguments): IConnection
    {
        $argumentsFile = Arrays::assocToIndex(Arrays::recombine($arguments));
        return match ($name) {
            'new' => match (true) {
                JSON::isValidJSON(...$argumentsFile) => self::callArgumentsByFormat('json', $argumentsFile),
                YAML::isValidYAML(...$argumentsFile) => self::callArgumentsByFormat('yaml', $argumentsFile),
                INI::isValidINI(...$argumentsFile) => self::callArgumentsByFormat('ini', $argumentsFile),
                XML::isValidXML(...$argumentsFile) => self::callArgumentsByFormat('xml', $argumentsFile),
                default => Arrays::isAssoc(...$argumentsFile)
                    ? self::callWithByStaticArray(...$arguments)
                    : self::callWithByStaticArgs($arguments)
            },
            default => call_user_func_array([self::getInstance(), $name], $arguments),
        };
    }
}
