<?php

namespace GenericDatabase\Engine\SQLite3;

use GenericDatabase\Engine\SQLite3Engine;

class Transaction
{
  protected static $transactionCounter = 0;

  protected static $inTransaction = false;

  public static function beginTransaction()
  {
    if (!self::$transactionCounter++) {
      return SQLite3Engine::getInstance()?->getConnection()?->begin_transaction();
      self::$inTransaction = true;
    }
    SQLite3Engine::getInstance()?->getConnection()?->exec('SAVEPOINT trans' . (self::$transactionCounter));
    return self::$transactionCounter >= 0;
  }

  public static function commit()
  {
    if (!--self::$transactionCounter) {
      return SQLite3Engine::getInstance()?->getConnection()?->commit();
      self::$inTransaction = false;
    }
    return self::$transactionCounter >= 0;
  }

  public static function inTransaction()
  {
    return self::$inTransaction;
  }

  public static function rollback()
  {
    if (--self::$transactionCounter) {
      SQLite3Engine::getInstance()?->getConnection()?->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
      self::$inTransaction = false;
      return true;
    }
    return SQLite3Engine::getInstance()?->getConnection()?->rollback();
  }
}
