<?php

namespace GenericDatabase\Engine\SQLite\Connection;

use GenericDatabase\Engine\SQLiteConnection;

class Transaction
{
    protected static int $transactionCounter = 0;

    protected static bool $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return SQLiteConnection::getInstance()->getConnection()->begin_transaction();
        }
        SQLiteConnection::getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return SQLiteConnection::getInstance()->getConnection()->commit();
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
            SQLiteConnection::getInstance()->getConnection()->exec(
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return SQLiteConnection::getInstance()->getConnection()->rollback();
    }
}
