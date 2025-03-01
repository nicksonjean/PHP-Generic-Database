<?php

namespace GenericDatabase\Engine\ODBC\Connection\Transactions;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\ITransactions;

class TransactionsHandler implements ITransactions
{
    protected static int $transactionCounter = 0;

    protected static bool $inTransaction = false;

    protected static IConnection $instance;

    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
    }

    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    public function beginTransaction(): bool
    {
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return odbc_autocommit($this->getInstance()->getConnection(), false);
        }
        odbc_exec($this->getInstance()->getConnection(), 'SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            $result = odbc_commit($this->getInstance()->getConnection());
            odbc_autocommit($this->getInstance()->getConnection(), true);
            return $result;
        } else {
            return self::$transactionCounter >= 0;
        }
    }

    public function inTransaction(): bool
    {
        return self::$inTransaction;
    }

    public function rollback(): bool
    {
        if (--self::$transactionCounter) {
            odbc_exec(
                $this->getInstance()->getConnection(),
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        $result = odbc_rollback($this->getInstance()->getConnection());
        odbc_autocommit($this->getInstance()->getConnection(), true);
        return $result;
    }
}
