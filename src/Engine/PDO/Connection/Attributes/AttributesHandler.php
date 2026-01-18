<?php

namespace GenericDatabase\Engine\PDO\Connection\Attributes;

use AllowDynamicProperties;
use GenericDatabase\Abstract\AbstractAttributes;
use GenericDatabase\Interfaces\Connection\IAttributes;
use GenericDatabase\Helpers\Exceptions;
use PDOException;
use Exception;

#[AllowDynamicProperties]
class AttributesHandler extends AbstractAttributes implements IAttributes
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
    public function define(): void
    {
        set_error_handler(
            function ($code, $message): never {
                throw new Exceptions(message: $message, code: $code);
            }
        );

        $result = [];
        foreach (self::$attributeList as $value) {
            try {
                $result[$value] = $this->getInstance()->getAttribute(constant("\PDO::ATTR_$value"));
            } catch (PDOException | Exception $e) {
                unset($e);
            }
        }
        restore_error_handler();
        $this->set('attributes', $result);
    }
}
