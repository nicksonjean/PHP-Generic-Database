<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\PgSQL\Connection\PgSQL;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for PgSQL database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? PgSQL::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            PgSQL::FETCH_OBJ,
            PgSQL::FETCH_INTO,
            PgSQL::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            PgSQL::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            PgSQL::FETCH_ASSOC => $this->internalFetchAssoc(),
            PgSQL::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? PgSQL::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            PgSQL::FETCH_OBJ,
            PgSQL::FETCH_INTO,
            PgSQL::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            PgSQL::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            PgSQL::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            PgSQL::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

