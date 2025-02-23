<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Options
{
    private static array $options = [];

    /**
     * This method is responsible for obtain all options already defined by user
     *
     * @param mixed $type = null
     * @return mixed
     */
    public static function getOptions(mixed $type = null): mixed
    {
        return !is_null($type) ? self::$options[$type] ?? null : self::$options;
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
        $class = PgSQL::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "PGSQL", $key)
                    : $key;
                PgSQLConnection::getInstance()->setAttribute("PgSQL::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    PgSQLConnection::getInstance()->setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] =
                        constant("$class::" . str_replace("PgSQL::", '', $options[$value]));
                } else {
                    self::$options[constant("$class::$key")] = $options[$value];
                }
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/pgsql.configuration.php
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
                $connection = PgSQLConnection::getInstance();
                if ($connection->getCharset()) {
                    pg_set_client_encoding($connection->getConnection(), $connection->getCharset());
                }
            }
        }
    }
}
