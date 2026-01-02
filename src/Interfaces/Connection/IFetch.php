<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines the contract for fetching data from a database.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IFetch
 */
interface IFetch
{
    /**
     * Executes the query and returns the result.
     *
     * @param int|null $fetchStyle The fetch style to use.
     * @param mixed|null $fetchArgument The fetch argument to use.
     * @param mixed|null $optArgs Additional options for the fetch operation.
     * @return mixed The query result.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed;

    /**
     * Executes the query and returns all the results.
     *
     * @param int|null $fetchStyle The fetch style to use.
     * @param mixed|null $fetchArgument The fetch argument to use.
     * @param mixed|null $optArgs Additional options for the fetch operation.
     * @return mixed The query result.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed;
}
