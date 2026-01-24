<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Fetch;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Abstract\AbstractFlatFileFetch;
use GenericDatabase\Engine\CSV\Connection\CSV;

/**
 * Handles fetch operations for CSV connections.
 * Extends AbstractFlatFileFetch to leverage common flat-file fetch functionality.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Fetch
 */
class FetchHandler extends AbstractFlatFileFetch implements IFlatFileFetch
{
    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     * @param IFetchStrategy|null $strategy The fetch strategy (optional).
     */
    public function __construct(IConnection $instance, ?IFetchStrategy $strategy = null)
    {
        // Create a default strategy if none provided
        $strategy = $strategy ?? new Strategy\FetchStrategy();
        parent::__construct($instance, $strategy);
    }

    /**
     * Execute a stored query and return the result set.
     * Implementation of abstract method from AbstractFlatFileFetch.
     *
     * @return array The result set.
     */
    protected function executeStoredQuery(): array
    {
        // Get data from connection
        if (method_exists($this->getInstance(), 'getData')) {
            return $this->getInstance()->getData();
        }
        return [];
    }

    /**
     * Fetch the next row from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The fetched row or false if no more rows.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = $fetchStyle ?? CSV::FETCH_ASSOC;

        return match ($fetch) {
            CSV::FETCH_OBJ => $this->internalFetchAssoc() !== false
                ? (object) $this->getResultSet()[$this->cursor - 1]
                : false,
            CSV::FETCH_INTO => $this->fetchIntoObject($fetchArgument),
            CSV::FETCH_CLASS => $this->internalFetchClass($optArgs, $fetchArgument ?? '\\stdClass'),
            CSV::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            CSV::FETCH_ASSOC => $this->internalFetchAssoc(),
            CSV::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetch all rows from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool The fetched rows or false on failure.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        $fetch = $fetchStyle ?? CSV::FETCH_ASSOC;

        return match ($fetch) {
            CSV::FETCH_OBJ => array_map(fn($row) => (object) $row, $this->internalFetchAllAssoc()),
            CSV::FETCH_INTO => $this->fetchAllIntoObjects($fetchArgument),
            CSV::FETCH_CLASS => $this->internalFetchAllClass($optArgs, $fetchArgument ?? '\\stdClass'),
            CSV::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            CSV::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            CSV::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }

    /**
     * Fetch row into an existing object.
     *
     * @param object|null $object The object to populate.
     * @return object|false The populated object or false.
     */
    private function fetchIntoObject(?object $object): object|false
    {
        $row = $this->internalFetchAssoc();
        if ($row === false) {
            return false;
        }
        return $this->fetchInto($row, $object);
    }

    /**
     * Fetch all rows into objects.
     *
     * @param object|null $object The object template.
     * @return array The populated objects.
     */
    private function fetchAllIntoObjects(?object $object): array
    {
        $results = $this->internalFetchAllAssoc();
        return array_map(fn($row) => $this->fetchInto($row, clone $object), $results);
    }
}
