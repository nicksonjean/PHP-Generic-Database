<?php

namespace GenericDatabase\Engine\SQLite;

use
    GenericDatabase\Traits\Path,

    GenericDatabase\Engine\SQLiteEngine;

class DSN
{
    public static function parseDsn(): string|\Exception
    {
        if (!extension_loaded('sqlite3')) {
            $message = sprintf(
                "Invalid or not loaded '%s' extension in '%s' settings",
                'sqlite3',
                'PHP.ini'
            );
            throw new \Exception($message);
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
