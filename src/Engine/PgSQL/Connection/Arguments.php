<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Engine\PgSQLConnection;
use ReflectionException;

class Arguments
{
    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     * @throws ReflectionException
     */
    private static function setConstant(array $value): array
    {
        $options = Generators::setConstant(
            $value,
            PgSQLConnection::getInstance(),
            'PgSQL',
            'PgSQL',
            ['ATTR_PERSISTENT', 'ATTR_CONNECT_TIMEOUT']
        );
        Options::setOptions($options);
        return Options::getOptions();
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
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return PgSQLConnection
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): PgSQLConnection
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
                    call_user_func_array(
                        [PgSQLConnection::getInstance(), 'set' . ucfirst($key)],
                        [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]
                    );
                } else {
                    call_user_func_array(
                        [PgSQLConnection::getInstance(), 'set' . ucfirst($key)],
                        [self::setType($value)]
                    );
                }
            }
        }
        return PgSQLConnection::getInstance();
    }


    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return PgSQLConnection
     */
    private static function callWithByStaticArray(array $arguments): PgSQLConnection
    {
        foreach ($arguments as $key => $value) {
            call_user_func_array([PgSQLConnection::getInstance(), 'set' . ucfirst($key)], [$value]);
        }
        return PgSQLConnection::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return PgSQLConnection
     * @noinspection PhpUnused
     */
    private static function callWithByStaticArgs(array $arguments): PgSQLConnection
    {
        return self::callWithByStaticArray($arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return PgSQLConnection
     * @throws ReflectionException
     */
    public static function call(string $name, array $arguments): PgSQLConnection
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
            default => call_user_func_array([PgSQLConnection::getInstance(), $name], $arguments)
        };
    }
}