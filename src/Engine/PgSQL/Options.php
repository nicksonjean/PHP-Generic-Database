<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Engine\PgSQLEngine;
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
        $class = \GenericDatabase\Engine\PgSQL\PgSQL::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT'
                    ? str_replace("ATTR", "PGSQL", $key)
                    : $key;
                PgSQLEngine::getInstance()->setAttribute("PgSQL::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
                    PgSQL::setAttribute($keyName, $options[$value]);
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
        $options = self::getOptions();
        $keys = array_keys($options);
        foreach ($keys as $key) {
            if ($key === 'ATTR_PERSISTENT' && ini_get('pgsql.allow_persistent') !== '1') {
                ini_set('pgsql.allow_persistent', 1);
            } else {
                $pgSQLEngine = PgSQLEngine::getInstance();
                if ($pgSQLEngine->getCharset()) {
                    pg_set_client_encoding($pgSQLEngine->getConnection(), $pgSQLEngine->getCharset());
                }
            }
        }
    }
}
