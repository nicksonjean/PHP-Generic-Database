<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Helpers\Validations;
use GenericDatabase\Helpers\Types;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Engine\PDOEngine;

class Arguments
{
    /**
     * array property for use in magic setter and getter in order
     */
    private static array $argumentList = [
        'Driver',
        'Host',
        'Port',
        'Database',
        'User',
        'Password',
        'Charset',
        'Options',
        'Exception'
    ];

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
        return Types::setType($value);
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
            unset(PDOEngine::getInstance()->host);
            unset(PDOEngine::getInstance()->port);
            unset(PDOEngine::getInstance()->user);
            unset(PDOEngine::getInstance()->password);
        }
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return PDOEngine
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): PDOEngine
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
                if (strtolower($key) === 'options') {
                    call_user_func_array(
                        [PDOEngine::getInstance(), 'set' . ucfirst($key)],
                        [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]
                    );
                } else {
                    self::resetArgs($value);
                    call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
                }
            }
        }
        return PDOEngine::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return PDOEngine
     */
    private static function callWithByStaticArgs(array $arguments): PDOEngine
    {
        if ($arguments[0] === 'sqlite') {
            self::resetArgs($arguments[0]);
            $clonedArgumentList = Arrays::exceptByValues(self::$argumentList, ['Host', 'Port', 'User', 'Password']);
            foreach ($arguments as $key => $value) {
                call_user_func_array([PDOEngine::getInstance(), 'set' . $clonedArgumentList[$key]], [$value]);
            }
        } else {
            foreach ($arguments as $key => $value) {
                call_user_func_array([PDOEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
            }
        }
        return PDOEngine::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return PDOEngine
     */
    private static function callWithByStaticArray(array $arguments): PDOEngine
    {
        self::resetArgs($arguments['driver']);
        foreach ($arguments as $key => $value) {
            call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [$value]);
        }
        return PDOEngine::getInstance();
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return PDOEngine
     */
    public static function call(string $name, array $arguments): PDOEngine
    {
        self::resetArgs($arguments[0]);
        return match ($name) {
            'new' => match (true) {
                JSON::isValidJSON(...$arguments) => self::callArgumentsByFormat('json', $arguments),
                YAML::isValidYAML(...$arguments) => self::callArgumentsByFormat('yaml', $arguments),
                INI::isValidINI(...$arguments) => self::callArgumentsByFormat('ini', $arguments),
                XML::isValidXML(...$arguments) => self::callArgumentsByFormat('xml', $arguments),
                default => Arrays::isAssoc(...$arguments)
                    ? self::callWithByStaticArray(...$arguments)
                    : self::callWithByStaticArgs($arguments)
            },
            default => call_user_func_array([PDOEngine::getInstance(), $name], $arguments)
        };
    }
}
