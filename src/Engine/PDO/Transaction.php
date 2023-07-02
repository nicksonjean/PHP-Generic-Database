<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

class Transaction
{
    protected static $transactionCounter = 0;

    protected static $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            return PDOEngine::getInstance()?->getConnection()?->begin_transaction();
            self::$inTransaction = true;
        }
        PDOEngine::getInstance()?->getConnection()?->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            return PDOEngine::getInstance()?->getConnection()?->commit();
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
            PDOEngine::getInstance()?->getConnection()?->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
            self::$inTransaction = false;
            return true;
        }
        return PDOEngine::getInstance()?->getConnection()?->rollback();
    }
}
