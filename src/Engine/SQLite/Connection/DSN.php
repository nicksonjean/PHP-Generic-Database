<?php

namespace GenericDatabase\Engine\SQLite\Connection;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteConnection;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parse(): string|CustomException
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new CustomException($message);
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
