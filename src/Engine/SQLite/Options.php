<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Helpers\Reflections;

class Options
{
    private static array $options = [];

    public static function flags(): int
    {
        $options = Options::getOptions();
        $result = [];

        foreach (Reflections::getClassConstants('GenericDatabase\Engine\SQLite\SQLite') as $value) {
            $attribute = "SQLite::ATTR_" . strtoupper($value);
            $attributeValue = SQLiteEngine::getInstance()->getAttribute($attribute);

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
     */
    public static function setOptions(?array $options = null): void
    {
        $class = 'GenericDatabase\Engine\SQLite\SQLite';
        foreach (Reflections::getClassConstants($class) as $key => $value) {
            $index = in_array($value, array_keys($options));
            if ($index !== false) {
                $keyName = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT' && $key !== 'ATTR_CONNECT_TIMEOUT'
                    ? str_replace("ATTR", "SQLITE3", $key)
                    : $key;
                SQLiteEngine::getInstance()->setAttribute("SQLite::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
                    SQLite::setAttribute($keyName, $options[$value]);
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
            if ($key === 'ATTR_PERSISTENT' && ini_get('sqlite.allow_persistent') !== '1') {
                ini_set('sqlite.allow_persistent', 1);
            }
        }
    }
}
