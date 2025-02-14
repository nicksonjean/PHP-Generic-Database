<?php

namespace GenericDatabase\Engine\PDO\Connection;

use Exception;
use PDOException;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Engine\PDOConnection;

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
     * Define all PDO attribute of the connection a ready exist
     *
     * @return void
     * @throws Exceptions|PDOException|Exception
     */
    public static function define(): void
    {
        set_error_handler(
            function ($code, $message): never {
                throw new Exceptions(message: $message, code: $code);
            }
        );

        $result = [];
        foreach (self::$attributeList as $value) {
            try {
                $result[$value] = PDOConnection::getInstance()->getAttribute(constant("\PDO::ATTR_$value"));
            } catch (PDOException | Exception $e) {
                unset($e);
            }
        }
        restore_error_handler();
        PDOConnection::getInstance()->setAttributes($result);
    }
}
