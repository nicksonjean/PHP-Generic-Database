<?php

namespace GenericDatabase\Engine\SQLite\Connection\Transactions;

use ErrorException;
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
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return $this->getInstance()->getConnection()->exec('BEGIN TRANSACTION');
        }
        $this->getInstance()->getConnection()->exec('SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        restore_error_handler();
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return $this->getInstance()->getConnection()->exec('COMMIT');
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
            $this->getInstance()->getConnection()->exec(
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return $this->getInstance()->getConnection()->exec('ROLLBACK');
    }
}
