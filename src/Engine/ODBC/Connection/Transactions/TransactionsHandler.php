<?php

namespace GenericDatabase\Engine\ODBC\Connection\Transactions;

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
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        if (!self::$transactionCounter++) {
            self::$inTransaction = true;
            return @odbc_autocommit($connection, false);
        }
        @odbc_exec($connection, 'SAVEPOINT trans' . (self::$transactionCounter));
        return self::$transactionCounter >= 0;
    }

    public function commit(): bool
    {
        restore_error_handler();
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        if (!--self::$transactionCounter) {
            self::$inTransaction = false;
            $result = @odbc_commit($connection);
            @odbc_autocommit($connection, true);
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
        $connection = $this->getInstance()->getConnection();
        if (!$connection) {
            return false;
        }
        if (--self::$transactionCounter) {
            @odbc_exec(
                $connection,
                'ROLLBACK TO trans' . (self::$transactionCounter + 1)
            );
            self::$inTransaction = false;
            return true;
        }
        $result = @odbc_rollback($connection);
        @odbc_autocommit($connection, true);
        return $result;
    }
}

