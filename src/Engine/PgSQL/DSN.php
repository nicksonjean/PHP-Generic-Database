<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Engine\PgSQLEngine;

class DSN
{
  public static function parseDns(): string|\Exception
  {
    if (!extension_loaded('pgsql')) {
      $message = sprintf(
        "Invalid or not loaded '%s' extension in '%s' settings",
        ['pgsql', 'PHP.ini']
      );
      throw new \Exception($message);
    }
    $result = null;
    $result = self::DSNPGSQL();
    PgSQLEngine::getInstance()->setDsn((string) $result);
    return $result;
  }

  private static function DSNPGSQL(): string
  {
    return sprintf(
      "host=%s port=%s dbname=%s user=%s password=%s%s options='--client_encoding=%s'",
      PgSQLEngine::getInstance()->getHost(),
      PgSQLEngine::getInstance()->getPort(),
      PgSQLEngine::getInstance()->getDatabase(),
      PgSQLEngine::getInstance()->getUser(),
      PgSQLEngine::getInstance()->getPassword(),
      Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) ? ' connect_timeout=' . Options::getOptions(PgSQL::ATTR_CONNECT_TIMEOUT) : '',
      PgSQLEngine::getInstance()->getCharset()
    );
  }
}
