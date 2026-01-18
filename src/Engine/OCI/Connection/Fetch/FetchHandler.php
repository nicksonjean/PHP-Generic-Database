<?php

namespace GenericDatabase\Engine\OCI\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\OCI\Connection\OCI;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for OCI database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? OCI::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            OCI::FETCH_OBJ,
            OCI::FETCH_INTO,
            OCI::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            OCI::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            OCI::FETCH_ASSOC => $this->internalFetchAssoc(),
            OCI::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? OCI::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            OCI::FETCH_OBJ,
            OCI::FETCH_INTO,
            OCI::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            OCI::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            OCI::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            OCI::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

