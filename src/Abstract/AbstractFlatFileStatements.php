<?php

declare(strict_types=1);

namespace GenericDatabase\Abstract;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Interfaces\Connection\IFlatFileStatements;
use GenericDatabase\Helpers\Parsers\QueryTypeDetector;

/**
 * Abstract base class for flat-file statement operations.
 * Provides common query handling for JSON/CSV engines.
 * Extends AbstractStatements and adds flat-file specific functionality.
 *
 * @package GenericDatabase\Abstract
 */
abstract class AbstractFlatFileStatements extends AbstractStatements implements IFlatFileStatements
{
    /**
     * @var int The number of fetched rows.
     */
    protected int $fetchedRows = 0;

    /**
     * @var int The last insert ID.
     */
    protected int $lastInsertIdValue = 0;

    /**
     * Constructor - passes through to parent.
     *
     * @param IConnection $instance Database connection instance.
     * @param IOptions $optionsHandler Options handler instance.
     * @param IReport $reportHandler Report handler instance.
     */
    public function __construct(IConnection $instance, IOptions $optionsHandler, IReport $reportHandler)
    {
        parent::__construct($instance, $optionsHandler, $reportHandler);
    }

    /**
     * Get the number of fetched rows.
     *
     * @return int
     */
    public function getFetchedRows(): int
    {
        return $this->fetchedRows;
    }

    /**
     * Set the number of fetched rows.
     *
     * @param int $params The number of fetched rows.
     * @return void
     */
    public function setFetchedRows(int $params): void
    {
        $this->fetchedRows = $params;
    }

    /**
     * Set the last insert ID.
     *
     * @param int $id The last insert ID.
     * @return void
     */
    public function setLastInsertId(int $id): void
    {
        $this->lastInsertIdValue = $id;
    }

    /**
     * Get the last insert ID.
     *
     * @param string|null $name The name (unused for flat files).
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->lastInsertIdValue;
    }

    /**
     * Detect the type of SQL query using robust detection.
     *
     * @param string $query The SQL query.
     * @return string The query type (SELECT, INSERT, UPDATE, DELETE, UNKNOWN).
     */
    protected function detectQueryType(string $query): string
    {
        return QueryTypeDetector::detect($query);
    }

    /**
     * Check if the query is a DML operation (INSERT, UPDATE, DELETE).
     *
     * @param string $query The SQL query.
     * @return bool
     */
    protected function isDmlQuery(string $query): bool
    {
        return QueryTypeDetector::isDmlQuery($query);
    }

    /**
     * Check if the query is a SELECT operation.
     *
     * @param string $query The SQL query.
     * @return bool
     */
    protected function isSelectQuery(string $query): bool
    {
        return QueryTypeDetector::isSelectQuery($query);
    }

    /**
     * Reset all metadata including fetched rows.
     *
     * @return void
     */
    public function setAllMetadata(): void
    {
        parent::setAllMetadata();
        $this->fetchedRows = 0;
    }

    /**
     * Quote a value for safe use in queries.
     *
     * @param mixed ...$params The value to quote.
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        $value = $params[0] ?? '';

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_null($value)) {
            return 'NULL';
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /**
     * Bind a parameter (no-op for flat files, parameters handled through setQueryParameters).
     *
     * @param object $params The parameter object.
     * @return void
     */
    public function bindParam(object $params): void
    {
        // Parameters are handled through queryParameters for flat files
    }

    /**
     * Parse a query (returns the query as-is for flat files).
     *
     * @param mixed ...$params The parameters.
     * @return string
     */
    public function parse(mixed ...$params): string
    {
        return $params[0] ?? '';
    }
}
