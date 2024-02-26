<?php

namespace GenericDatabase\Engine\Firebird;

use GenericDatabase\Engine\FirebirdEngine;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Options
{
    private static array $options = [];

    /**
     * This method is responsible for obtaining all options already defined by the user.
     *
     * @param ?int $type = null
     * @return mixed
     */
    public static function getOptions(?int $type = null): mixed
    {
        if (!is_null($type)) {
            $result = self::$options[$type] ?? null;
        } else {
            $result = self::$options;
        }
        return $result;
    }

    /**
     * This method is responsible for set options before connect in database
     *
     * @param ?array $options = null
     * @return void
     * @throws ReflectionException
     */
    public static function setOptions(?array $options = null): void
    {
        $class = \GenericDatabase\Engine\Firebird\Firebird::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT'
                    ? str_replace("ATTR", "FIREBIRD", $key)
                    : $key;
                FirebirdEngine::getInstance()->setAttribute("Firebird::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
                    Firebird::setAttribute($keyName, $options[$value]);
                }
                self::$options[constant("$class::$key")] = $options[$value];
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database
     *
     * @return void
     */
    public static function define(): void
    {
        foreach (array_keys(self::getOptions()) as $key) {
            if ($key === 'ATTR_PERSISTENT' && ini_get('ibase.allow_persistent') !== '1') {
                ini_set('ibase.allow_persistent', 1);
            }
        }
    }
}
