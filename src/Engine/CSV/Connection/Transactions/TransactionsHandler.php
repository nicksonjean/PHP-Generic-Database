<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Transactions;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\ITransactions;
use GenericDatabase\Engine\CSV\Connection\Structure\StructureHandler;

/**
 * Handles transaction operations for CSV connections.
 * Implements backup/restore mechanism for flat file transactions.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Transactions
 */
class TransactionsHandler implements ITransactions
{
    /**
     * Transaction counter for nested transactions
     * @var int
     */
    protected static int $transactionCounter = 0;

    /**
     * Transaction state flag
     * @var bool
     */
    protected static bool $inTransaction = false;

    /**
     * Backup data for transaction rollback
     * @var array|null
     */
    protected static ?array $transactionBackup = null;

    /**
     * Connection instance
     * @var IConnection
     */
    protected static IConnection $instance;

    /**
     * Structure handler; strategy from getStructureStrategy() is used for commit/rollback.
     * @var StructureHandler|null
     */
    protected static ?StructureHandler $structureHandler = null;

    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     * @param StructureHandler|null $structureHandler Structure handler; strategy from getStructureStrategy() is used for commit/rollback.
     */
    public function __construct(IConnection $instance, ?StructureHandler $structureHandler = null)
    {
        self::$instance = $instance;
        self::$structureHandler = $structureHandler;
    }

    /**
     * Get the connection instance.
     *
     * @return IConnection
     */
    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Begin a transaction.
     * For CSV files, this creates a backup of the current data.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        if (self::$inTransaction) {
            self::$transactionCounter++;
            return true;
        }

        // Backup current data for potential rollback
        $strategy = self::$structureHandler?->getStructureStrategy();
        if ($strategy !== null) {
            self::$transactionBackup = $strategy->getData();
        } elseif (method_exists($this->getInstance(), 'getData')) {
            self::$transactionBackup = $this->getInstance()->getData();
        }

        self::$inTransaction = true;
        self::$transactionCounter = 1;
        return true;
    }

    /**
     * Commit the transaction.
     * For CSV files, this saves the current data to file.
     *
     * @return bool
     */
    public function commit(): bool
    {
        if (!self::$inTransaction) {
            return false;
        }

        self::$transactionCounter--;

        if (self::$transactionCounter > 0) {
            return true;
        }

        // Save data to file
        $result = true;
        $strategy = self::$structureHandler?->getStructureStrategy();
        if ($strategy !== null) {
            $result = $strategy->save($strategy->getData());
        } elseif (method_exists($this->getInstance(), 'save') && method_exists($this->getInstance(), 'getData')) {
            $result = $this->getInstance()->save($this->getInstance()->getData());
        }

        self::$inTransaction = false;
        self::$transactionBackup = null;
        self::$transactionCounter = 0;

        return $result;
    }

    /**
     * Rollback the transaction.
     * For CSV files, this restores the backup data.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        if (!self::$inTransaction) {
            return false;
        }

        self::$transactionCounter--;

        if (self::$transactionCounter > 0) {
            return true;
        }

        // Restore backup data
        if (self::$transactionBackup !== null) {
            $strategy = self::$structureHandler?->getStructureStrategy();
            if ($strategy !== null) {
                $strategy->setData(self::$transactionBackup);
            } elseif (method_exists($this->getInstance(), 'setData')) {
                $this->getInstance()->setData(self::$transactionBackup);
            }
        }

        self::$inTransaction = false;
        self::$transactionBackup = null;
        self::$transactionCounter = 0;

        return true;
    }

    /**
     * Check if currently in a transaction.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return self::$inTransaction;
    }
}
