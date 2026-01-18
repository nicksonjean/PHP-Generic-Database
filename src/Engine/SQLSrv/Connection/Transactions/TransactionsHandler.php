<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Transactions;

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
            return sqlsrv_begin_transaction($this->getInstance()->getConnection());
        }
        sqlsrv_query($this->getInstance()->getConnection(), 'SAVE TRANSACTION trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        restore_error_handler();
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            return sqlsrv_commit($this->getInstance()->getConnection());
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
            sqlsrv_query(
                $this->getInstance()->getConnection(),
                'ROLLBACK TRANSACTION trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        return sqlsrv_rollback($this->getInstance()->getConnection());
    }
}
