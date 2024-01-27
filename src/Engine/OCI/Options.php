<?php

namespace GenericDatabase\Engine\OCI;

use GenericDatabase\Engine\OCIEngine;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Options
{
    private static array $options = [];

    /**
     * This method is responsible for obtain all options already defined by user
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
        $class = \GenericDatabase\Engine\OCI\OCI::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT'
                    ? str_replace("ATTR", "OCI", $key)
                    : $key;
                OCIEngine::getInstance()->setAttribute("OCI::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
                    OCI::setAttribute($keyName, $options[$value]);
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
            if ($key === 'ATTR_PERSISTENT' && ini_get('oci8.persistent_timeout') !== '1') {
                ini_set('oci8.persistent_timeout ', Options::getOptions(OCI::ATTR_CONNECT_TIMEOUT));
            }
        }
    }
}
