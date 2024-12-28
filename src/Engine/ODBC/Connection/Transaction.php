<?php

namespace GenericDatabase\Engine\ODBC\Connection;

use GenericDatabase\Engine\ODBCConnection;

class Transaction
{
    protected static int $transactionCounter = 0;

    protected static bool $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return ODBCConnection::getInstance()->getConnection()->begin_transaction();
        }
        ODBCConnection::getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return ODBCConnection::getInstance()->getConnection()->commit();
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
            ODBCConnection::getInstance()->getConnection()->exec('ROLLBACK TO trans' . (self::$transactionCounter + 1));
            self::$inTransaction = false;
            return true;
        }
        return ODBCConnection::getInstance()->getConnection()->rollback();
    }
}
