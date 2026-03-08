<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers\SQL\Query;

/**
 * Data Transfer Object for SQL query analysis information.
 *
 * @package GenericDatabase\Helpers\Parsers\SQL\Query
 */
class Info
{
    /**
     * Create a new Info instance.
     *
     * @param string $primaryType The primary type of the query (SELECT, INSERT, UPDATE, DELETE, UNKNOWN).
     * @param bool $isCompound Whether the query is a compound query (e.g., INSERT...SELECT).
     * @param bool $hasSubquery Whether the query contains subqueries.
     * @param array $operations List of all operations detected in the query.
     * @param array $tables List of tables involved in the query (when extractable).
     */
    public function __construct(
        public readonly string $primaryType,
        public readonly bool $isCompound,
        public readonly bool $hasSubquery,
        public readonly array $operations,
        public readonly array $tables
    ) {}

    /**
     * Check if this is a SELECT query.
     *
     * @return bool
     */
    public function isSelect(): bool
    {
        return $this->primaryType === TypeDetector::TYPE_SELECT;
    }

    /**
     * Check if this is an INSERT query.
     *
     * @return bool
     */
    public function isInsert(): bool
    {
        return $this->primaryType === TypeDetector::TYPE_INSERT;
    }

    /**
     * Check if this is an UPDATE query.
     *
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->primaryType === TypeDetector::TYPE_UPDATE;
    }

    /**
     * Check if this is a DELETE query.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->primaryType === TypeDetector::TYPE_DELETE;
    }

    /**
     * Check if this is a DML query (INSERT, UPDATE, DELETE).
     *
     * @return bool
     */
    public function isDml(): bool
    {
        return in_array($this->primaryType, [
            TypeDetector::TYPE_INSERT,
            TypeDetector::TYPE_UPDATE,
            TypeDetector::TYPE_DELETE
        ]);
    }
}
