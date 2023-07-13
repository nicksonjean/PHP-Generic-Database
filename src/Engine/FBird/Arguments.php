<?php

namespace GenericDatabase\Engine\FBird;

use GenericDatabase\Helpers\Types;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
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
     * Transform variables in constants
     *
     * @param array $value
     * @return array
     */
    private static function setConstant($value): array
    {
        $options = Types::setConstant(
            $value,
            FBirdEngine::getInstance(),
            'FBird',
            'FBird',
            ['ATTR_PERSISTENT', 'ATTR_CONNECT_TIMEOUT']
        );
        Options::setOptions($options);
        return Options::getOptions();
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
                        [FBirdEngine::getInstance(), 'set' . ucfirst($key)],
                        [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]
                    );
                } else {
                    call_user_func_array([FBirdEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
                }
            }
        }
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @param array $arguments
     * @return void
     */
    private static function callWithByStaticArray(array $arguments): void
    {
        foreach ($arguments as $key => $value) {
            call_user_func_array([FBirdEngine::getInstance(), 'set' . ucfirst($key)], [$value]);
        }
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @param array $arguments
     * @return void
     */
    private static function callWithByStaticArgs(array $arguments): void
    {
        foreach ($arguments as $key => $value) {
            call_user_func_array([FBirdEngine::getInstance(), 'set' . self::$argumentList[$key]], [$value]);
        }
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @param string $name
     * @param array $arguments
     * @return FBirdEngine
     */
    public static function call(string $name, array $arguments): FBirdEngine
    {
        match ($name) {
            'new' => match (true) {
                JSON::isValidJSON(...$arguments) => self::callArgumentsByFormat('json', $arguments),
                YAML::isValidYAML(...$arguments) => self::callArgumentsByFormat('yaml', $arguments),
                INI::isValidINI(...$arguments) => self::callArgumentsByFormat('ini', $arguments),
                XML::isValidXML(...$arguments) => self::callArgumentsByFormat('xml', $arguments),
                default => Arrays::isAssoc(...$arguments)
                    ? self::callWithByStaticArray(...$arguments)
                    : self::callWithByStaticArgs($arguments)
            },
            default => call_user_func_array([FBirdEngine::getInstance(), $name], $arguments)
        };
        return FBirdEngine::getInstance();
    }
}
