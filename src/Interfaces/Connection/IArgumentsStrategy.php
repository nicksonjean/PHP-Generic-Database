<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines the strategy for handling arguments in database connections.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IArgumentsStrategy
 */
interface IArgumentsStrategy
{
    /**
     * Sets a constant value for the argument's strategy.
     *
     * @param array $value The constant value to set.
     * @return array The updated argument's strategy.
     */
    public function setConstant(array $value): array;
}

