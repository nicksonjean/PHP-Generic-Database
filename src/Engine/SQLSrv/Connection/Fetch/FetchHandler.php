<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Engine\SQLSrv\Connection\SQLSrv;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for SQLSrv database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? SQLSrv::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            SQLSrv::FETCH_OBJ,
            SQLSrv::FETCH_INTO,
            SQLSrv::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            SQLSrv::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            SQLSrv::FETCH_ASSOC => $this->internalFetchAssoc(),
            SQLSrv::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? SQLSrv::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            SQLSrv::FETCH_OBJ,
            SQLSrv::FETCH_INTO,
            SQLSrv::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            SQLSrv::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            SQLSrv::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            SQLSrv::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}
