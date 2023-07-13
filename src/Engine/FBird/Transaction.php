<?php

namespace GenericDatabase\Engine\FBird;

use GenericDatabase\Engine\FBirdEngine;

class Transaction
{
    protected static $transactionCounter = 0;

    protected static $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return FBirdEngine::getInstance()->getConnection()->begin_transaction();
        }
        FBirdEngine::getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return FBirdEngine::getInstance()->getConnection()->commit();
        } else {
            return self::$transactionCounter >= 0;
        }
    }

    public static function inTransaction()
    {
        return self::$inTransaction;
    }

    public static function rollback()
    {
        if (--self::$transactionCounter) {
            FBirdEngine::getInstance()->getConnection()->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
            self::$inTransaction = false;
            return true;
        }
        return FBirdEngine::getInstance()->getConnection()->rollback();
    }
}
