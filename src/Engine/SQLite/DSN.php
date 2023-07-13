<?php

namespace GenericDatabase\Engine\SQLite;

use AllowDynamicProperties;
use GenericDatabase\Engine\SQLiteEngine;
use GenericDatabase\Helpers\Path;
use GenericDatabase\Helpers\GenericException;

#[AllowDynamicProperties]
class DSN
{
    public static function parseDsn(): string|GenericException
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new GenericException($message);
        }

        if (!Path::isAbsolute(SQLiteEngine::getInstance()->getDatabase())) {
            SQLiteEngine::getInstance()->setDatabase(Path::toAbsolute(SQLiteEngine::getInstance()->getDatabase()));
        }

        $result = null;
        $result = sprintf(
            "sqlite:%s?charset=%s",
            SQLiteEngine::getInstance()->getDatabase(),
            SQLiteEngine::getInstance()->getCharset()
        );

        SQLiteEngine::getInstance()->setDsn((string) $result);
        return $result;
    }
}
