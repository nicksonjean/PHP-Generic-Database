<?php

namespace GenericDatabase\Engine\PDO\Connection;

use GenericDatabase\Helpers\Validations;
use GenericDatabase\Helpers\Generators;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Engine\PDOConnection;

class Arguments
{
    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     */
    private static function setConstant(array $value): array
    {
        $result = [];
        foreach (Arrays::recombine(...$value) as $key => $value) {
            if (Validations::isNumber($value) && !Validations::isBoolean($value)) {
                $result[constant($key)] = (int) $value;
            } elseif (Validations::isBoolean($value)) {
                $result[constant($key)] = (bool) $value;
            } else {
                $result[constant($key)] = constant($value);
            }
        }
        return $result;
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
     * Remove unused objects from PDO SQLite
     *
     * @param mixed $driver
     * @return void
     */
    private static function resetArgs(mixed $driver): void
    {
        if ($driver === 'sqlite') {
            unset(PDOConnection::getInstance()->host);
            unset(PDOConnection::getInstance()->port);
            unset(PDOConnection::getInstance()->user);
            unset(PDOConnection::getInstance()->password);
        }
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return PDOConnection
     * @noinspection PhpUnused
     * @noinspection PhpUnused
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): PDOConnection
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
                        [PDOConnection::getInstance(), 'set' . ucfirst($key)],
                        [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]
                    );
                } else {
                    self::resetArgs($value);
                    call_user_func_array(
                        [PDOConnection::getInstance(), 'set' . ucfirst($key)],
                        [self::setType($value)]
                    );
                }
            }
        }
        return PDOConnection::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return PDOConnection
     */
    private static function callWithByStaticArray(array $arguments): PDOConnection
    {
        self::resetArgs($arguments['driver']);
        foreach ($arguments as $key => $value) {
            call_user_func_array([PDOConnection::getInstance(), 'set' . ucfirst($key)], [$value]);
        }
        return PDOConnection::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return PDOConnection
     * @noinspection PhpUnused
     * @noinspection PhpUnused
     */
    private static function callWithByStaticArgs(array $arguments): PDOConnection
    {
        return self::callWithByStaticArray($arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return PDOConnection
     */
    public static function call(string $name, array $arguments): PDOConnection
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
            default => call_user_func_array([PDOConnection::getInstance(), $name], $arguments)
        };
    }
}
