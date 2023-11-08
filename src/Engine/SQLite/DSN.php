<?php

namespace GenericDatabase\Engine\SQLite;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\CustomException;

#[AllowDynamicProperties]
class DSN
{
    /**
     * @throws CustomException
     */
    public static function parseDsn(): string|CustomException
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
                SQLiteEngine::getInstance()->getDatabase()
            ) && SQLiteEngine::getInstance()->getDatabase() != 'memory'
        ) {
            SQLiteEngine::getInstance()->setDatabase(Path::toAbsolute(
                SQLiteEngine::getInstance()->getDatabase()
            ));
            $result = sprintf(
                "sqlite:%s",
                SQLiteEngine::getInstance()->getDatabase()
            );
        } else {
            $result = sprintf(
                "sqlite::%s:",
                SQLiteEngine::getInstance()->getDatabase()
            );
        }

        SQLiteEngine::getInstance()->setDsn($result);
        return $result;
    }
}
