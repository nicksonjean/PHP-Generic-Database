<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface provides methods to set and get report mode, and handle errors.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IReport
 */
interface IReport
{
    /**
     * Sets the report mode.
     *
     * @param mixed $mode The mode to set for the report.
     * @return void
     */
    public function setReportMode($mode): void;

    /**
     * Gets the current report mode.
     *
     * @return mixed The current report mode.
     */
    public function getReportMode(): mixed;

    /**
     * Handles an error with an optional message.
     *
     * @param string|null $message The error message to handle.
     * @return void
     */
    public function handleError($message = null): void;
}
