<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Helpers\Generators;
use GenericDatabase\Helpers\Arrays;
use GenericDatabase\Helpers\JSON;
use GenericDatabase\Helpers\INI;
use GenericDatabase\Helpers\YAML;
use GenericDatabase\Helpers\XML;
use GenericDatabase\Engine\MySQLiEngine;
use ReflectionException;

class Arguments
{
    /**
     * Transform variables in constants
     *
     * @return array
     * @throws ReflectionException
     */
    private static function setConstant(array $value): array
    {
        $options = Generators::setConstant(
            $value,
            MySQLiEngine::getInstance(),
            'MySQL',
            'MySQLi',
            ['ATTR_PERSISTENT', 'ATTR_AUTOCOMMIT']
        );
        Options::setOptions($options);
        return Options::getOptions();
    }

    /**
     * Determines the type that will receive treatment
     *
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
     * @return MySQLiEngine
     * @throws ReflectionException
     */
    private static function callArgumentsByFormat(string $format, mixed $arguments): MySQLiEngine
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
                        [MySQLiEngine::getInstance(), 'set' . ucfirst($key)],
                        [self::setConstant(($format === 'json' || $format === 'yaml') ? $value : [$value])]
                    );
                } else {
                    call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [self::setType($value)]);
                }
            }
        }
        return MySQLiEngine::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static array format
     *
     * @return MySQLiEngine
     */
    private static function callWithByStaticArray(array $arguments): MySQLiEngine
    {
        foreach ($arguments as $key => $value) {
            call_user_func_array([MySQLiEngine::getInstance(), 'set' . ucfirst($key)], [$value]);
        }
        return MySQLiEngine::getInstance();
    }

    /**
     * This method is used when all parameters are used in the static arguments format
     *
     * @return MySQLiEngine
     */
    private static function callWithByStaticArgs(array $arguments): MySQLiEngine
    {
        return self::callWithByStaticArray($arguments);
    }

    /**
     * This method works like a factory and is responsible for identifying
     * the way in which the class is instantiated, as well as its arguments.
     *
     * @return MySQLiEngine
     * @throws ReflectionException
     */
    public static function call(string $name, array $arguments): MySQLiEngine
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
            default => call_user_func_array([MySQLiEngine::getInstance(), $name], $arguments)
        };
    }
}
