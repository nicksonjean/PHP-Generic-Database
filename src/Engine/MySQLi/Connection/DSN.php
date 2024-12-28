<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\MySQLiConnection;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('mysqli')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'mysqli',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        $result = sprintf(
            "mysql://%s:%s@%s:%s/%s?charset=%s",
            MySQLiConnection::getInstance()->getUser(),
            MySQLiConnection::getInstance()->getPassword(),
            MySQLiConnection::getInstance()->getHost(),
            MySQLiConnection::getInstance()->getPort(),
            MySQLiConnection::getInstance()->getDatabase(),
            MySQLiConnection::getInstance()->getCharset()
        );

        MySQLiConnection::getInstance()->setDsn($result);
        return $result;
    }
}
