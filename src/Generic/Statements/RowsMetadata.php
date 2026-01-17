<?php

namespace GenericDatabase\Generic\Statements;

/**
 * This class represents metadata for database rows, including the number of rows fetched
 * and affected by a query. Provides methods to get and set these values.
 *
 * Methods:
 * - `getFetched(): int`: Retrieves the fetch results.
 * - `setFetched(int $fetched): self`: Sets the fetch results.
 * - `getAffected(): int`: Retrieves the affected results.
 * - `setAffected(int $affected): self`: Sets the affected results.
 *
 * Fields:
 * - `$fetched`: The number of rows fetched.
 * - `$affected`: The number of rows affected.
 */
class RowsMetadata
{
    /**
     * @var int $fetched The number of rows fetched.
     */
    public int $fetched = 0;

    /**
     * @var int $affected The number of rows affected by the statement.
     */
    public int $affected = 0;

    /**
     * Gets the number of rows fetched.
     *
     * @return int The number of rows fetched.
     */
    public function getFetched(): int
    {
        return $this->fetched;
    }

    /**
     * Sets the number of rows fetched.
     *
     * @param int $fetched The number of rows fetched.
     * @return $this
     */
    public function setFetched(int $fetched): self
    {
        $this->fetched = $fetched;
        return $this;
    }

    /**
     * Gets the number of rows affected.
     *
     * @return int The number of rows affected.
     */
    public function getAffected(): int
    {
        return $this->affected;
    }

    /**
     * Sets the number of rows affected.
     *
     * @param int $affected The number of rows affected.
     * @return $this
     */
    public function setAffected(int $affected): self
    {
        $this->affected = $affected;
        return $this;
    }
}
