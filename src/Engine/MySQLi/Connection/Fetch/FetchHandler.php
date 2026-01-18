<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\MySQLi\Connection\MySQL;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for MySQLi database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? MySQL::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            MySQL::FETCH_OBJ,
            MySQL::FETCH_INTO,
            MySQL::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            MySQL::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            MySQL::FETCH_ASSOC => $this->internalFetchAssoc(),
            MySQL::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? MySQL::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            MySQL::FETCH_OBJ,
            MySQL::FETCH_INTO,
            MySQL::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            MySQL::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            MySQL::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            MySQL::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

