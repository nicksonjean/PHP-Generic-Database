<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\CSV\Connection\CSV;

/**
 * Handles fetch operations for CSV connections.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Fetch
 */
class FetchHandler implements IFetch
{
    /**
     * @var IConnection The connection instance.
     */
    protected static IConnection $instance;

    /**
     * @var mixed The fetch strategy.
     */
    private static mixed $strategy;

    /**
     * @var int Current cursor position.
     */
    private int $cursor = 0;

    /**
     * @var array|null Cached result set.
     */
    private ?array $resultSet = null;

    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     */
    public function __construct(IConnection $instance)
    {
        self::$instance = $instance;
    }

    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Set the result set for fetching.
     *
     * @param array $resultSet The result set.
     * @return void
     */
    public function setResultSet(array $resultSet): void
    {
        $this->resultSet = $resultSet;
        $this->cursor = 0;
    }

    /**
     * Fetch the next row from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The fetched row or false if no more rows.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->getInstance()->getData();
        }

        if ($this->cursor >= count($this->resultSet)) {
            return false;
        }

        $row = $this->resultSet[$this->cursor++];
        return $this->formatRow($row, $fetchStyle ?? CSV::FETCH_ASSOC, $fetchArgument);
    }

    /**
     * Fetch all rows from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool The fetched rows or false on failure.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->getInstance()->getData();
        }

        $result = [];
        $style = $fetchStyle ?? CSV::FETCH_ASSOC;

        foreach ($this->resultSet as $row) {
            $result[] = $this->formatRow($row, $style, $fetchArgument);
        }

        $this->cursor = count($this->resultSet);
        return $result;
    }

    /**
     * Format a row based on the fetch style.
     *
     * @param mixed $row The row to format.
     * @param int $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @return mixed The formatted row.
     */
    private function formatRow(mixed $row, int $fetchStyle, mixed $fetchArgument = null): mixed
    {
        $row = (array) $row;

        return match ($fetchStyle) {
            CSV::FETCH_NUM => array_values($row),
            CSV::FETCH_BOTH => array_merge($row, array_values($row)),
            CSV::FETCH_OBJ => (object) $row,
            CSV::FETCH_COLUMN => $fetchArgument !== null
                ? ($row[$fetchArgument] ?? array_values($row)[0] ?? null)
                : (array_values($row)[0] ?? null),
            default => $row,
        };
    }
}
