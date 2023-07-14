<?php

namespace GenericDatabase\Engine\PDO;

use Exception;
use PDOException;
use ErrorException;
use GenericDatabase\Engine\PDOEngine;

class Attributes
{
    /**
     * static attributes constants
     *
     */
    public static array $attributeList = [
        'AUTOCOMMIT',
        'ERRMODE',
        'CASE',
        'CLIENT_VERSION',
        'CONNECTION_STATUS',
        'ORACLE_NULLS',
        'PERSISTENT',
        'PREFETCH',
        'SERVER_INFO',
        'SERVER_VERSION',
        'DRIVER_NAME',
        'TIMEOUT',
        'STRINGIFY_FETCHES',
        'EMULATE_PREPARES',
        'DEFAULT_FETCH_MODE'
    ];

    /**
     * Define all PDO attibute of the conection a ready exist
     *
     * @return void
     * @throws ErrorException
     */
    public static function define(): void
    {
        set_error_handler(
            function ($severity, $message, $file, $line) {
                throw new ErrorException($message, $severity, $severity, $file, $line);
            }
        );

        $result = [];
        foreach (self::$attributeList as $value) {
            try {
                $result[$value] = PDOEngine::getInstance()->getAttribute(constant("\PDO::ATTR_$value"));
            } catch (PDOException | Exception $e) {
                unset($e);
            }
        }
        restore_error_handler();
        PDOEngine::getInstance()->setAttributes($result);
    }
}
