<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Engine\MySQLiEngine;

class DSN
{
  public static function parseDsn(): string|\Exception
  {
    if (!extension_loaded('mysqli')) {
      $message = sprintf(
        "Invalid or not loaded '%s' extension in '%s' settings",
        ['mysqli', 'PHP.ini']
      );
      throw new \Exception($message);
    }
    $result = null;
    $result = self::DSNMDB2MySQL();
    MySQLiEngine::getInstance()->setDsn((string) $result);
    return $result;
  }

  private static function DSNMDB2MySQL(): string
  {
    return sprintf(
      "mysql://%s:%s@%s:%s/%s?charset=%s",
      MySQLiEngine::getInstance()->getUser(),
      MySQLiEngine::getInstance()->getPassword(),
      MySQLiEngine::getInstance()->getHost(),
      MySQLiEngine::getInstance()->getPort(),
      MySQLiEngine::getInstance()->getDatabase(),
      MySQLiEngine::getInstance()->getCharset()
    );
  }
}
