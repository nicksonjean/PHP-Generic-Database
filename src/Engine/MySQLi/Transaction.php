<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Engine\MySQLiEngine;

class Transaction
{
  protected static $transactionCounter = 0;

  protected static $inTransaction = false;

  public static function beginTransaction()
  {
    if (!self::$transactionCounter++) {
      return MySQLiEngine::getInstance()?->getConnection()?->begin_transaction();
      self::$inTransaction = true;
    }
    MySQLiEngine::getInstance()?->getConnection()?->exec('SAVEPOINT trans' . (self::$transactionCounter));
    return self::$transactionCounter >= 0;
  }

  public static function commit()
  {
    if (!--self::$transactionCounter) {
      return MySQLiEngine::getInstance()?->getConnection()?->commit();
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
      MySQLiEngine::getInstance()?->getConnection()?->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
      self::$inTransaction = false;
      return true;
    }
    return MySQLiEngine::getInstance()?->getConnection()?->rollback();
  }
}