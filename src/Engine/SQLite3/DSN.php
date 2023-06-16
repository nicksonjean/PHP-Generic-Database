<?php

namespace GenericDatabase\Engine\SQLite3;

use
  GenericDatabase\Traits\Path,
  GenericDatabase\Engine\SQLite3Engine;

class DSN
{
  public static function parseDsn(): string|\Exception
  {
    if (!extension_loaded('sqlite3')) {
      $message = sprintf(
        "Invalid or not loaded '%s' extension in '%s' settings",
        ['sqlite3', 'PHP.ini']
      );
      throw new \Exception($message);
    }

    if (!Path::isAbsolute(SQLite3Engine::getInstance()->getDatabase())) {
      SQLite3Engine::getInstance()->setDatabase(Path::toAbsolute(SQLite3Engine::getInstance()->getDatabase()));
    }

    $result = null;
    $result = sprintf(
      "sqlite3:%s?charset=%s",
      SQLite3Engine::getInstance()->getDatabase(),
      SQLite3Engine::getInstance()->getCharset()
    );

    SQLite3Engine::getInstance()->setDsn((string) $result);
    return $result;
  }
}
