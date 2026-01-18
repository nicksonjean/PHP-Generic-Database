<?php

namespace GenericDatabase\Engine\PDO\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use PDO;
use GenericDatabase\Abstract\AbstractFetch;
use ReflectionException;

/**
 * Concrete implementation for PDO database
 */
class FetchHandler extends AbstractFetch implements IFetch
{
    /**
     * Fetches the next row from a result set
     * @throws ReflectionException
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $fetch = is_null($fetchStyle) ? PDO::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => $this->internalFetchClass($fetchArgument ?? null, $optArgs),
            PDO::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            PDO::FETCH_ASSOC => $this->internalFetchAssoc(),
            PDO::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
    }

    /**
     * Fetches all rows from a result set
     * @throws ReflectionException
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = 0, mixed $optArgs = null): array|bool
    {
        $fetch = is_null($fetchStyle) ? PDO::FETCH_BOTH : $fetchStyle;

        return match ($fetch) {
            PDO::FETCH_OBJ,
            PDO::FETCH_INTO,
            PDO::FETCH_CLASS => $this->internalFetchAllClass($fetchArgument ?? null, $optArgs),
            PDO::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            PDO::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            PDO::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }
}

