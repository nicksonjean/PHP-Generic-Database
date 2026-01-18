<?php

namespace GenericDatabase\Engine\SQLite\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\SQLite\Connection\SQLite;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for SQLite database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? SQLite::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            SQLite::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            SQLite::FETCH_ASSOC => $this->internalFetchAssoc(),
            SQLite::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? SQLite::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            SQLite::FETCH_OBJ,
            SQLite::FETCH_INTO,
            SQLite::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            SQLite::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            SQLite::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            SQLite::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

