<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Traits\Reflections;

#[\AllowDynamicProperties]
class Options
{
    use Reflections;

    private static $options = [];

    public static function flags(): int
    {
        $options = [];
        $options = Options::getOptions();
        $result = '';
        foreach (Reflections::getClassConstants('GenericDatabase\Engine\SQLite\SQLite') as $key => $value) {
            $index = array_search($value, array_keys($options));
            if ($index !== false) {
                if ($value <= 4) {
                    switch ($value) {
                        case 1: // ATTR_OPEN_READONLY
                            if (SQLiteEngine::getInstance()->getAttribute("SQLite::ATTR_OPEN_READONLY") === true) {
                                $result .= $value . "+";
                            }
                            break;
                        case 2: // ATTR_OPEN_READWRITE
                            if (SQLiteEngine::getInstance()->getAttribute("SQLite::ATTR_OPEN_READWRITE") === true) {
                                if (SQLiteEngine::getInstance()->getAttribute("SQLite::ATTR_OPEN_READONLY") === true) {
                                    $result = str_replace("1+", "", $result);
                                }
                                $result .= $value . "+";
                            }
                            break;
                        case 4: // ATTR_OPEN_CREATE
                            if (SQLiteEngine::getInstance()->getAttribute("SQLite::ATTR_OPEN_CREATE") === true) {
                                if (SQLiteEngine::getInstance()->getAttribute("SQLite::ATTR_OPEN_READONLY") === true) {
                                    $result = str_replace("1+", "", $result);
                                }
                                $result .= $value . "+";
                            }
                            break;
                    }
                }
            }
        }

        $calculate = (function ($str) {
            eval("\$str = $str;");
            return $str;
        });
        $result = $result !== '' ? $calculate(rtrim($result, "+")) : 6;
        return $result === 2 || $result === 4 ? 6 : $result;
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
            $result = isset(self::$options[$type]) ? self::$options[$type] : null;
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
            $index = array_search($value, array_keys($options));
            if ($index !== false) {
                $key_name = $key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT' && $key !== 'ATTR_CONNECT_TIMEOUT' ? str_replace("ATTR", "SQLITE3", $key) : $key;
                SQLiteEngine::getInstance()->setAttribute("SQLite::$key", $options[$value]);
                if ($key !== 'ATTR_PERSISTENT' && $key !== 'ATTR_AUTOCOMMIT' && $key !== 'ATTR_CONNECT_TIMEOUT') {
                    SQLite::setAttribute($key_name, $options[$value]);
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
        foreach (self::getOptions() as $key => $value) {
            switch ($key) {
                case 'ATTR_PERSISTENT':
                    if (ini_get('sqlite.allow_persistent') !== '1') {
                        ini_set('sqlite.allow_persistent', 1);
                    }
                    break;
                default:
            }
        }
    }
}
