<?php

declare(strict_types=1);

namespace GenericDatabase\Helpers\Parsers;

/**
 * Data Transfer Object for SQL query analysis information.
 *
 * @package GenericDatabase\Helpers\Parsers
 */
class QueryInfo
{
    /**
     * Create a new QueryInfo instance.
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
    ) {
    }

    /**
     * Check if this is a SELECT query.
     *
     * @return bool
     */
    public function isSelect(): bool
    {
        return $this->primaryType === QueryTypeDetector::TYPE_SELECT;
    }

    /**
     * Check if this is an INSERT query.
     *
     * @return bool
     */
    public function isInsert(): bool
    {
        return $this->primaryType === QueryTypeDetector::TYPE_INSERT;
    }

    /**
     * Check if this is an UPDATE query.
     *
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->primaryType === QueryTypeDetector::TYPE_UPDATE;
    }

    /**
     * Check if this is a DELETE query.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->primaryType === QueryTypeDetector::TYPE_DELETE;
    }

    /**
     * Check if this is a DML query (INSERT, UPDATE, DELETE).
     *
     * @return bool
     */
    public function isDml(): bool
    {
        return in_array($this->primaryType, [
            QueryTypeDetector::TYPE_INSERT,
            QueryTypeDetector::TYPE_UPDATE,
            QueryTypeDetector::TYPE_DELETE
        ]);
    }
}
