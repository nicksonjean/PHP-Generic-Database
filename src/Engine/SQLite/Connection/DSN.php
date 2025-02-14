<?php

namespace GenericDatabase\Engine\SQLite\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\Exceptions;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws Exceptions
     */
    public static function parse(): string|Exceptions
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new Exceptions($message);
        }

        if (
            !Path::isAbsolute(
                SQLiteConnection::getInstance()->getDatabase()
            ) && SQLiteConnection::getInstance()->getDatabase() != 'memory'
        ) {
            SQLiteConnection::getInstance()->setDatabase(Path::toAbsolute(
                SQLiteConnection::getInstance()->getDatabase()
            ));
            $result = sprintf(
                "sqlite:%s",
                SQLiteConnection::getInstance()->getDatabase()
            );
        } else {
            $result = sprintf(
                "sqlite::%s:",
                SQLiteConnection::getInstance()->getDatabase()
            );
        }

        SQLiteConnection::getInstance()->setDsn($result);
        return $result;
    }
}
