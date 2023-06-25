<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Engine\SQLiteEngine;

class Transaction
{
  protected static $transactionCounter = 0;

  protected static $inTransaction = false;

  public static function beginTransaction()
  {
    if (!self::$transactionCounter++) {
      return SQLiteEngine::getInstance()?->getConnection()?->begin_transaction();
      self::$inTransaction = true;
    }
    SQLiteEngine::getInstance()?->getConnection()?->exec('SAVEPOINT trans' . (self::$transactionCounter));
    return self::$transactionCounter >= 0;
  }

  public static function commit()
  {
    if (!--self::$transactionCounter) {
      return SQLiteEngine::getInstance()?->getConnection()?->commit();
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
      SQLiteEngine::getInstance()?->getConnection()?->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
      self::$inTransaction = false;
      return true;
    }
    return SQLiteEngine::getInstance()?->getConnection()?->rollback();
  }
}
