<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Transactions;

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
            return $this->getInstance()->getConnection()->begin_transaction();
        }
        $this->getInstance()->getConnection()->query('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return $this->getInstance()->getConnection()->commit();
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
            $this->getInstance()->getConnection()->query(
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return $this->getInstance()->getConnection()->rollback();
    }
}

