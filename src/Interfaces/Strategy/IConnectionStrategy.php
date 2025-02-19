<?php

namespace GenericDatabase\Interfaces\Strategy;

use GenericDatabase\Interfaces\IConnection;

interface IConnectionStrategy
{
    /**
     * Defines the strategy instance
     *
     * @param mixed $strategy
     * @return void
     */
    public function setStrategy(IConnection $strategy): void;

    /**
     * Get the strategy instance
     *
     * @return mixed
     */
    public function getStrategy(): IConnection;
}
