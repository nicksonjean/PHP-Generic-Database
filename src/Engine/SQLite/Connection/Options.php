<?php

namespace GenericDatabase\Engine\SQLite\Connection;

use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Options
{
    private static array $options = [];

    /**
     * @throws ReflectionException
     */
    public static function flags(): int
    {
        $options = Options::getOptions();
        $result = [];

        foreach (Reflections::getClassConstants(SQLite::class) as $value) {
            $attribute = "SQLite::ATTR_" . mb_strtoupper((string) $value);
            $attributeValue = SQLiteConnection::getInstance()->getAttribute($attribute);

            if ($attributeValue === true && in_array($attribute, $options)) {
                if ($value === 1) {
                    $result[] = $value;
                } elseif ($value === 2 || $value === 4) {
                    $result = [$value];
                    break;
                }
            }
        }

        if (empty($result)) {
            $result = [6];
        }

        return $result[0];
    }

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
        $class = SQLite::class;
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT'
                    ? str_replace("ATTR", "SQLITE3", $key)
                    : $key;
                SQLiteConnection::getInstance()->setAttribute("SQLite::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_CONNECT_TIMEOUT' && $key !== 'ATTR_AUTOCOMMIT') {
                    SQLiteConnection::getInstance()->setAttribute($keyName, $options[$value]);
                }
                if (str_contains($options[$value], 'FETCH')) {
                    self::$options[constant("$class::$key")] =
                        constant("$class::" . str_replace("SQLite::", '', $options[$value]));
                } else {
                    self::$options[constant("$class::$key")] = $options[$value];
                }
            }
        }
    }

    /**
     * This method is responsible for set options after connect in database,
     * more information's in https://www.php.net/manual/en/sqlite3.configuration.php
     *
     * @return void
     */
    public static function define(): void
    {
        $options = self::getOptions();
        $keys = array_keys($options);
        foreach ($keys as $key) {
            if ($key === 'ATTR_PERSISTENT' && ini_get('sqlite.allow_persistent') !== '1') {
                ini_set('sqlite.allow_persistent', 1);
            }
        }
    }
}
