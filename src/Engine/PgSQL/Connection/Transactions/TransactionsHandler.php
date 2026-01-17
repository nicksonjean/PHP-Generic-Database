<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Transactions;

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
            return pg_query($this->getInstance()->getConnection(), 'BEGIN') !== false;
        }
        pg_query($this->getInstance()->getConnection(), 'SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        restore_error_handler();
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return pg_query($this->getInstance()->getConnection(), 'COMMIT') !== false;
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
            pg_query(
                $this->getInstance()->getConnection(),
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return pg_query($this->getInstance()->getConnection(), 'ROLLBACK') !== false;
    }
}
