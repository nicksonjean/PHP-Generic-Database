<?php

namespace GenericDatabase\Engine\FBird;

use GenericDatabase\Traits\Types;
use GenericDatabase\Traits\JSON;
use GenericDatabase\Traits\INI;
use GenericDatabase\Traits\YAML;
use GenericDatabase\Traits\XML;
use GenericDatabase\Engine\FBirdEngine;

class Arguments
{
    /**
     * array property for use in magic setter and getter in order
     */
    private static $argumentList = [
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
            call_user_func_array([FBirdEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
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
        $options = Types::setConstant($value, FBirdEngine::getInstance(), 'FBird', 'FBird', ['ATTR_PERSISTENT', 'ATTR_CONNECT_TIMEOUT']);
        Options::setOptions($options);
        $options = Options::getOptions();
        return $options;
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
                    call_user_func_array([FBirdEngine::getInstance(), 'set' . ucfirst($key)], [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]);
                } else {
                    call_user_func_array([FBirdEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
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
        call_user_func_array([FBirdEngine::getInstance(), $method], $arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $method
     * @param array $arguments
     * @return FBirdEngine
     */
    public static function call(string $method, array $arguments): mixed
    {
        switch ($method) {
            case 'new':
            case 'create':
            case 'config':
                if (count($arguments) === 8) {
                    self::callWithFullArguments($arguments);
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
        return FBirdEngine::getInstance();
    }
}
