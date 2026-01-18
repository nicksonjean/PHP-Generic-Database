<?php

namespace GenericDatabase\Engine\ODBC\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\ODBC\Connection\ODBC;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for ODBC database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? ODBC::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_INTO,
            ODBC::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            ODBC::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            ODBC::FETCH_ASSOC => $this->internalFetchAssoc(),
            ODBC::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? ODBC::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            ODBC::FETCH_OBJ,
            ODBC::FETCH_INTO,
            ODBC::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            ODBC::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            ODBC::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            ODBC::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

