<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * Defines the interface for an arguments strategy that can set constant values.
 */
interface IArgumentsStrategy
{
    /**
     * Sets a constant value for the arguments strategy.
     *
     * @param array $value The constant value to set.
     * @return array The updated arguments strategy.
     */
    public function setConstant(array $value): array;
}
