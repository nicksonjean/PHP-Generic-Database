<?php

namespace GenericDatabase\Engine\Firebird\Connection;

use GenericDatabase\Engine\FirebirdConnection;

class Transaction
{
    protected static int $transactionCounter = 0;

    protected static bool $inTransaction = false;

    public static function beginTransaction()
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return FirebirdConnection::getInstance()->getConnection()->begin_transaction();
        }
        FirebirdConnection::getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public static function commit()
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return FirebirdConnection::getInstance()->getConnection()->commit();
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
            FirebirdConnection::getInstance()->getConnection()->exec(
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return FirebirdConnection::getInstance()->getConnection()->rollback();
    }
}
