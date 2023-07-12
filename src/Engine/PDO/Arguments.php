<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Traits\Regex;
use GenericDatabase\Traits\Arrays;
use GenericDatabase\Traits\Types;
use GenericDatabase\Traits\JSON;
use GenericDatabase\Traits\INI;
use GenericDatabase\Traits\YAML;
use GenericDatabase\Traits\XML;
use GenericDatabase\Engine\PDOEngine;

class Arguments
{
    /**
     * array property for use in magic setter and getter in order
     */
    private static $argumentList = [
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
     * This method is used when all parameters are used
     *
     * @param array $arguments
     * @return void
     */
    private static function callWithFullArguments($arguments): void
    {
        foreach ($arguments as $key => $value) {
            call_user_func_array([PDOEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
        }
    }

    /**
     * This method is used when any of the parameters are omitted
     *
     * @param array $arguments
     * @return void
     */
    private static function callWithPartialArguments($arguments): void
    {
        $clonedArgumentList = Arrays::exceptByValues(self::$argumentList, ['Host', 'Port', 'User', 'Password']);
        foreach ($arguments as $key => $value) {
            call_user_func_array([PDOEngine::getInstance(), 'set' . $clonedArgumentList[$key]], [$value]);
        }
    }

    /**
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     */
    private static function setConstant($value): array
    {
        $result = [];
        foreach (Arrays::recombine(...$value) as $key => $value) {
            if (Regex::isNumber($value) && !Regex::isBoolean($value)) {
                $result[constant($key)] = (int) $value;
            } elseif (Regex::isBoolean($value)) {
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
     * @return mixed
     */
    private static function setType($value): mixed
    {
        return Types::setType($value);
    }

    /**
     * Determines arguments type by calling to format type
     *
     * @param string $format Accept formats json, xml, ini and yaml
     * @param mixed $arguments
     * @return void
     */
    private static function callArgumentsByFormat($format, $arguments): void
    {
        $data = null;
        if ($format === 'json') {
            $data = JSON::parseJSON(...$arguments);
        } elseif ($format === 'ini') {
            $data = INI::parseINI(...$arguments);
        } elseif ($format === 'xml') {
            $data = XML::parseXML(...$arguments);
        } elseif ($format === 'yaml') {
            $data = YAML::parseYAML(...$arguments);
        }

        if ($data) {
            foreach ($data as $key => $value) {
                if (strtolower($key) === 'options') {
                    call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]);
                } else {
                    call_user_func_array([PDOEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
                }
            }
        }
    }

    /**
     * Determines arguments type by calling to default type
     *
     * @param mixed $arguments
     * @return void
     */
    private static function callArgumentsByDefault($method, $arguments): void
    {
        call_user_func_array([PDOEngine::getInstance(), $method], $arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $method
     * @param array $arguments
     * @return PDOEngine
     */
    public static function call(string $method, array $arguments): mixed
    {
        switch ($method) {
            case 'new':
            case 'create':
            case 'config':
                if (count($arguments) === 9) {
                    self::callWithFullArguments($arguments);
                } elseif (count($arguments) === 5) {
                    self::callWithPartialArguments($arguments);
                } else {
                    if (JSON::isValidJSON(...$arguments)) {
                        self::callArgumentsByFormat('json', $arguments);
                    } elseif (YAML::isValidYAML(...$arguments)) {
                        self::callArgumentsByFormat('yaml', $arguments);
                    } elseif (INI::isValidINI(...$arguments)) {
                        self::callArgumentsByFormat('ini', $arguments);
                    } elseif (XML::isValidXML(...$arguments)) {
                        self::callArgumentsByFormat('xml', $arguments);
                    }
                }
                break;
            default:
                self::callArgumentsByDefault($method, $arguments);
                break;
        }
        return PDOEngine::getInstance();
    }
}
