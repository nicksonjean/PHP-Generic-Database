<?php

namespace GenericDatabase\Engine\PgSQL\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\PgSQLConnection;
use GenericDatabase\Helpers\Exceptions;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws Exceptions
     */
    public static function parse(): string|Exceptions
    {
        if (!extension_loaded('pgsql')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pgsql',
                'PHP.ini'
            );
            throw new Exceptions($message);
        }

        $result = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s%s options='--client_encoding=%s'",
            PgSQLConnection::getInstance()->getHost(),
            PgSQLConnection::getInstance()->getPort(),
            PgSQLConnection::getInstance()->getDatabase(),
            PgSQLConnection::getInstance()->getUser(),
            PgSQLConnection::getInstance()->getPassword(),
            Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                ? ' connect_timeout=' . Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                : '',
            PgSQLConnection::getInstance()->getCharset()
        );

        PgSQLConnection::getInstance()->setDsn($result);
        return $result;
    }
}
