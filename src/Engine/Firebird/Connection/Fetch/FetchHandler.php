<?php

namespace GenericDatabase\Engine\Firebird\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\Firebird\Connection\Firebird;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for Firebird database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? Firebird::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            Firebird::FETCH_OBJ,
            Firebird::FETCH_INTO,
            Firebird::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            Firebird::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            Firebird::FETCH_ASSOC => $this->internalFetchAssoc(),
            Firebird::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? Firebird::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            Firebird::FETCH_OBJ,
            Firebird::FETCH_INTO,
            Firebird::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            Firebird::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            Firebird::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            Firebird::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

