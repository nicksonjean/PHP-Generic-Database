<?php

namespace GenericDatabase\Helpers;

class DSNHash
{
    private static array $dsnFile;

    public static function loadDsn(): array
    {
        if (!isset(self::$dsnFile)) {
            $json = __DIR__ . DIRECTORY_SEPARATOR . 'ODBC' . DIRECTORY_SEPARATOR . 'DSN.json';
            self::$dsnFile = json_decode(file_get_contents($json), true);
        }
        return self::$dsnFile;
    }
}
