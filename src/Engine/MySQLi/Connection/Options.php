<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use GenericDatabase\Engine\MySQLiConnection;
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
        $class = MySQL::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "MYSQLI", $key)
                    : $key;
                MySQLiConnection::getInstance()->setAttribute("MySQL::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    MySQL::setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] =
                        constant("$class::" . str_replace("MySQL::", '', $options[$value]));
                } else {
                    self::$options[constant("$class::$key")] = $options[$value];
                }
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/mysqli.configuration.php
     *
     * @return void
     */
    public static function define(): void
    {
        foreach (self::getOptions() as $key => $value) {
            switch ($key) {
                case 'ATTR_PERSISTENT':
                    if (ini_get('mysqli.allow_persistent') !== '1') {
                        ini_set('mysqli.allow_persistent', 1);
                    }
                    break;
                case 'ATTR_OPT_LOCAL_INFILE':
                    if (ini_get('mysqli.allow_local_infile') !== '1') {
                        ini_set('mysqli.allow_local_infile', 1);
                    }
                    break;
                case 'ATTR_INIT_COMMAND':
                    MySQLiConnection::getInstance()->getConnection()->query($value);
                    break;
                case 'ATTR_SET_CHARSET_NAME':
                    MySQLiConnection::getInstance()->getConnection()->set_charset($value);
                    break;
                case 'ATTR_OPT_CONNECT_TIMEOUT':
                    MySQLiConnection::getInstance()->getConnection()->query(
                        "SET GLOBAL connect_timeout=" . $value
                    );
                    MySQLiConnection::getInstance()->getConnection()->query(
                        "SET SESSION interactive_timeout=" . $value
                    );
                    MySQLiConnection::getInstance()->getConnection()->query(
                        "SET SESSION wait_timeout=" . $value
                    );
                    break;
                case 'ATTR_OPT_READ_TIMEOUT':
                    MySQLiConnection::getInstance()->getConnection()->query(
                        "SET SESSION net_read_timeout=" . $value
                    );
                    MySQLiConnection::getInstance()->getConnection()->query(
                        "SET SESSION net_write_timeout=" . ($value * 2)
                    );
                    break;
                default:
                    MySQLiConnection::getInstance()->getConnection()->query("SET SESSION sql_mode=''");
                    if (MySQLiConnection::getInstance()->getCharset()) {
                        MySQLiConnection::getInstance()->getConnection()->set_charset(
                            MySQLiConnection::getInstance()->getCharset()
                        );
                    }
            }
        }
    }
}
