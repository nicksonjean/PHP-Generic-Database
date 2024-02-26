<?php

namespace GenericDatabase\Engine\Firebird;

use GenericDatabase\Engine\FirebirdEngine;

class Transaction
{
    protected static int $transactionCounter = 0;

    protected static bool $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return FirebirdEngine::getInstance()->getConnection()->begin_transaction();
        }
        FirebirdEngine::getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return FirebirdEngine::getInstance()->getConnection()->commit();
        } else {
            return self::$transactionCounter >= 0;
        }
    }

    public static function inTransaction(): bool
    {
        return self::$inTransaction;
    }

    public static function rollback()
    {
        if (--self::$transactionCounter) {
            FirebirdEngine::getInstance()->getConnection()->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
            self::$inTransaction = false;
            return true;
        }
        return FirebirdEngine::getInstance()->getConnection()->rollback();
    }
}
