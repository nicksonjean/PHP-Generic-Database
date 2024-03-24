<?php

namespace GenericDatabase\Engine\PgSQL;

use AllowDynamicProperties;
use GenericDatabase\Engine\PgSQLEngine;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('pgsql')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'pgsql',
                'PHP.ini'
            );
            throw new CustomException($message);
        }

        $result = sprintf(
            "host=%s port=%s dbname=%s user=%s password=%s%s options='--client_encoding=%s'",
            PgSQLEngine::getInstance()->getHost(),
            PgSQLEngine::getInstance()->getPort(),
            PgSQLEngine::getInstance()->getDatabase(),
            PgSQLEngine::getInstance()->getUser(),
            PgSQLEngine::getInstance()->getPassword(),
            Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                ? ' connect_timeout=' . Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT)
                : '',
            PgSQLEngine::getInstance()->getCharset()
        );

        PgSQLEngine::getInstance()->setDsn($result);
        return $result;
    }
}
